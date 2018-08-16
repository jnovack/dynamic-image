<?php

class Image {

    public $height = -1;
    public $width = -1;
    public $format = "application/octet-stream";
    private $image;

    public function __construct($filename) {
        if (!file_exists(realpath($filename))) {
            return false;
        }

        $this->image = new Imagick($filename);
        $this->format = "image/" . strtolower($this->image->getImageFormat());
        $this->getDimensions();

   }

    public function generate($options) {
        if (isset($options['dir_overlays']) && !is_dir($options['dir_overlays'])) {
            error_log('dir_overlays ' . $options['dir_overlays'] . ' does not exist');
        }

        /* Adjust Brightness */
        if(isset($options['brightness'])) {
            $this->adjustBrightness($options['brightness']);
        }

        /* Crop */
        if(isset($options['cropWidth']) || isset($options['cropHeight'])) {
            $cW = isset($options['cropWidth']) ? $options['cropWidth'] : $this->width;
            $cH = isset($options['cropHeight']) ? $options['cropHeight'] : $this->height;

            $X = isset($options['cropX']) ? $options['cropX'] : 'center';
            $Y = isset($options['cropY']) ? $options['cropY'] : 'center';

            switch ($Y) {
                case 'top':
                    $cY = 0;
                    break;
                case 'bottom':
                    $cY = $this->height - $cH;
                    break;
                case 'center' :
                    $cY = ($this->height - $cH) / 2;
                    break;
            }

            switch ($X) {
                case 'left' :
                    $cX = 0;
                    break;
                case 'right' :
                    $cX = $this->width - $cW;
                    break;
                case 'center' :
                    $cX = ($this->width - $cW) / 2;
                    break;
            }
            $this->cropImage($cW, $cH, $cX, $cY);

        }

        /* Adjust Size */
        if(isset($options['width']) || isset($options['height'])) {
            $w = isset($options['width']) ? $options['width'] : 0;
            $h = isset($options['height']) ? $options['height'] : 0;
            $this->adjustSize($w, $h);
        }

        /* Adjust Quality */
        if(isset($options['quality'])) {
            $this->compressImage($options['quality']);
        }

        /* Adjust Blur */
        if(isset($options['blur'])) {
            $this->blurImage($options['blur']);
        }

        /* Add Overlay to Desired Image */
        if(isset($options['overlay']) && is_readable($options['dir_overlays'])) {
            $this->generateOverlay($options['dir_overlays'], $options['overlay'], $options['gravity']);
        }

        return $this->image;
    }

    /*
     *****************************************************************
     *   Private Functions
     *****************************************************************
     */

    /**
    * Adjust brightness of Image
    * @param  int $brightness   Level of brightness to apply.
    * @return void              Brightness-Adjusted Image.
    */
    private function adjustBrightness($brightness) {
        $this->image->brightnessContrastImage($brightness, 0, 134217727);
    }

    /**
    * Adjust size of Image
    * @param  int $width        Final width in pixels.
    * @param  int $height       Final height in pixels.
    * @return void              Resized Image.
    */
    private function adjustSize($width, $height) {
        $this->image->thumbnailImage($width, $height);
        $this->getDimensions();
    }

    /**
    * Add blur to Image.
    * @param  int $blur         Level of blur.
    * @return void              Blurred Image.
    */
    private function blurImage($blur) {
        // Blurring a tiny image is computationally sexier than blurring a full-size
        // image, with marginal loss in visual quality (you are blurring it after all.)
        $this->image->thumbnailImage(240, 0);
        $this->image->blurImage(0, $blur);
        $this->image->thumbnailImage($this->width, 0);
    }

    /**
    * Reduce quality of Image.
    * @param  [type] $quality   Compress quality of final image.
    * @return void              Lower quality Image.
    */
    private function compressImage($quality) {
        $this->image->setImageCompressionQuality($quality);
    }

    /**
    * Crop Image.
    * @param  int $cW           Width of the crop.
    * @param  int $cH           Height of the crop.
    * @param  int $cX           X coordinate of the cropped region's top left corner.
    * @param  int $cY           Y coordinate of the cropped region's top left corner.
    * @return void              Cropped Image.
    */
    private function cropImage($cW, $cH, $cX, $cY) {
        $this->image->cropImage($cW, $cH, $cX, $cY);
        $this->getDimensions();
    }

    /**
    * Get the dimensions of the image, set them as a public variables.
    * @return void              Sets public variables 'width' and 'height'.
    */
    private function getDimensions() {
        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }

    /***********************************/

    /**
    * Add an overlay to the Image.
    * @param  string $directory Directory where the overlays are found.
    * @param  string $overlay   Overlay filename, without '.png'.
    * @param  string $gravity   Direction to pull the overlay over the image.
    * @return void              Image with overlay attached.
    */
    private function generateOverlay($directory, $overlay, $gravity) {
        $overlay_file = $directory . '/' . $overlay . '.png';
        if ( !file_exists($overlay_file) ) {
            error_log('generateOverlay() ERROR: '. $overlay_file . ' does not exist.');
            return;
        }

        // Load Overlay
        $overlay = new Imagick();
        $overlay->readImage($overlay_file);
        $overlay->scaleImage($this->width, 0);

        // Gravity
        $x = 0;
        $y = 0;
        switch ($gravity) {
            case "northeast":
                $x = $this->width - $overlay->getImageWidth();
                break;
            case "southeast":
                $x = $this->width - $overlay->getImageWidth();
                $y = $this->height - $overlay->getImageHeight();
                break;
            case "southwest":
                $y = $this->height - $overlay->getImageHeight();
                break;
        }

        $this->image->compositeImage($overlay, imagick::COMPOSITE_OVER, $x, $y);
    }
}

?>