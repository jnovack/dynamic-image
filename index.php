<?php
/* Metrics */
$time = 0-microtime(true);

/* ENVIRONMENT */
$overlay_directory = getenv('PHP_DIRECTORY_OVERLAYS', true) ?: realpath('./overlays');
$images_directory = getenv('PHP_DIRECTORY_IMAGES', true) ?: realpath('./images');

/* Valid options for enums */
$enums = array(
    'overlay'       => array('soldout', 'cancelled', 'delayed'),
    'gravity'       => array('northeast', 'northwest', 'southeast', 'southwest'),
    'disclaimer'    => array('disclaimer', 'disclaimertop'),
    'cropX'         => array('top','center','bottom'),
    'cropY'         => array('left','center','right'),
);

/*************************************************************************/

/* Validation */
if (!is_dir($images_directory) || !is_readable($images_directory)) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 501 Not Implemented', true, 501);
    error_log('php-dynamic-image is not properly configured. Please set PHP_DIRECTORY_IMAGES environment variable to a valid path.');
    exit(1);
}

/* Autoloader */
spl_autoload_register(function ($class_name) {
    include 'classes/' . $class_name . '.php';
});

/* Sanitize filename */
$filters = array(
    /* Permits files in (root) and sub-directories of (root).  Fail if attempted ".." */
    'file'          => array('filter'     => FILTER_VALIDATE_REGEXP,
                             'options'    => array( "regexp" => "/^((?!\.\.).)*([a-z0-9][a-z\-0-9\/]{1,80})*[a-z0-9][a-z\-0-9]{1,80}\.[jpng]{3}$/" )
                     )
);

$SANITIZED_GET = array_filter(filter_input_array(INPUT_GET, $filters));

// Set option from environment
if (is_dir($overlay_directory) && is_readable($overlay_directory)) {
    $SANITIZED_GET['dir_overlays'] = $overlay_directory;
}

/* Load File */
if ( empty($SANITIZED_GET) || empty($SANITIZED_GET['file']) || $SANITIZED_GET['file'] === false) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 400 Bad Request', true, 400);
    exit(1);
}

$filename = $images_directory . $SANITIZED_GET['file'];

if ( !file_exists($filename) ) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found', true, 404);
    exit(1);
}

/* Begin Output */
header("Content-disposition: inline; filename='".basename($filename)."'");

switch(substr($filename, -3)) {
    case "jpg": $contenttype = "image/jpeg"; break;
    case "png": $contenttype = "image/png"; break;
}
header("Content-Type: " . $contenttype);

/* Begin Processing */
$img = new Image($filename);

/* Sanitize Options for Mmage Manipulation */
$rules = array(
    'brightness'    =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => -100, 'max_range' => 100)
                        ),
    'blur'          =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 1, 'max_range' => 50)
                        ),
    'quality'       =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 50, 'max_range' => 99)
                        ),
    'width'         =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 1, 'max_range' => $img->width)
                        ),
    'height'         => array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 1, 'max_range' => $img->height)
                        ),
    'cropWidth'     =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 1, 'max_range' => $img->width)
                        ),
    'cropHeight'    =>  array('filter'    => FILTER_VALIDATE_INT,
                              'flags'     => FILTER_REQUIRE_SCALAR,
                              'options'   => array('min_range' => 1, 'max_range' => $img->height)
                        ),
    'cropX'         =>  array('filter'    => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['cropX'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'cropY'         =>  array('filter'    => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['cropY'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'overlay'       =>  array('filter'    => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['overlay'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'gravity'       =>  array('filter'    => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['gravity'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'disclaimer'    =>  array('filter'    => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['disclaimer'], "cast" => "string")), 'FILTER_ENUM')
                        )
);

/* Filter $_GET according to the $rules above, then remove blank/NULL/false values */
$SANITIZED_OPTS = array_filter(filter_input_array(INPUT_GET, $rules));

// Set default if it does not exist
$SANITIZED_OPTS['gravity'] = isset($SANITIZED_OPTS['gravity']) ? $SANITIZED_OPTS['gravity'] : 'northwest';

/* Job's done! */
header("Content-Type: " . $img->format);
header("X-DynamicImage-Options: " . json_encode($SANITIZED_OPTS));
$output = $img->generate($SANITIZED_OPTS, $SANITIZED_GET);
$time += microtime(true);
header("X-DynamicImage-Time: " . $time);
echo $output;
imagedestroy($img);

?>
