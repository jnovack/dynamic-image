<?php

/* CUSTOMIZATION */
$directory = "./images/";

/*************************************************************************/

/* Autoloader */
spl_autoload_register(function ($class_name) {
    include 'classes/' . $class_name . '.php';
});

/* Valid options for enums */
$enums = array(
    'state'         => array('soldout', 'cancelled', 'delayed'),
    'overlay'       => array('disclaimer', 'disclaimertop'),
    'cropX'         => array('top','center','bottom'),
    'cropY'         => array('left','center','right'),
);

/* Sanitize filename */
$filters = array(
    /* Permits files in (root) and sub-directories of (root).  Fail if attempted ".." */
    'file'          => array('filter'     => FILTER_VALIDATE_REGEXP,
                             'options'    => array( "regexp" => "/^((?!\.\.).)*([a-z0-9][a-z\-0-9\/]{1,80})*[a-z0-9][a-z\-0-9]{1,80}\.[jpng]{3}$/" )
                     )
);

$SANITIZED_GET = array_filter(filter_input_array(INPUT_GET, $filters));

if ( empty($SANITIZED_GET) || $SANITIZED_GET['file'] === false) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 400 Bad Request', true, 400);
    exit(1);
}

$filename = $directory . $SANITIZED_GET['file'];

if ( !file_exists($filename) ) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found', true, 404);
    exit(1);
}

/* Load image */
$img = new Image($filename);

/* Sanitize options for image manipulation */
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
    'cropX'         =>  array('filter'     => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['cropX'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'cropY'         =>  array('filter'     => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['cropY'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'state'         =>  array('filter'     => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['state'], "cast" => "string")), 'FILTER_ENUM')
                        ),
    'overlay'       =>  array('filter'     => FILTER_CALLBACK,
                             'options'    => array(new Sanitize( array("values" => $enums['overlay'], "cast" => "string")), 'FILTER_ENUM')
                        )
);

/* Filter $_GET according to the $rules above, then remove blank/NULL/false values */
$SANITIZED_OPTS = array_filter(filter_input_array(INPUT_GET, $rules));

/* Job's done! */
header("Content-Type: " . $img->format);
header("Content-disposition: inline; filename='".basename($filename)."'");
header("X-ImageMagick-Options: " . json_encode($SANITIZED_OPTS));
echo $img->generate($SANITIZED_OPTS);

?>
