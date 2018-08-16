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
    * [adjustBrightness description]
    * @param  [type] $brightness [description]
    * @return [type]             [description]
    */
    private function adjustBrightness($brightness) {
        $this->image->brightnessContrastImage($brightness, 0, 134217727);
    }

    /**
    * [adjustSize description]
    * @param  [type] $width      [description]
    * @param  [type] $height     [description]
    * @return [type]             [description]
    */
    private function adjustSize($width, $height) {
        $this->image->thumbnailImage($width, $height);
        $this->getDimensions();
    }

    /**
    * [blurImage description]
    * @param  [type] $blur  [description]
    * @param  [type] $width [description]
    * @return [type]        [description]
    */
    private function blurImage($blur) {
        $this->image->thumbnailImage(240, 0);
        $this->image->blurImage(0, $blur);
        $this->image->thumbnailImage($this->width, 0);
    }

    /**
    * [compressImage description]
    * @param  [type] $quality [description]
    * @return [type]          [description]
    */
    private function compressImage($quality) {
        $this->image->setImageCompressionQuality($quality);
    }

    /**
    * [cropImage description]
    * @param  [type] $cW    [description]
    * @param  [type] $cH    [description]
    * @param  [type] $cX    [description]
    * @param  [type] $cY    [description]
    * @return [type]        [description]
    */
    private function cropImage($cW, $cH, $cX, $cY) {
        $this->image->cropImage($cW, $cH, $cX, $cY);
        $this->getDimensions();
    }

    /**
    * [getDimensions description]
    * @return [type]             [description]
    */
    private function getDimensions() {
        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }

    /***********************************/

    /**
    * [generateOverlay description]
    * @param  [type] $directory [description]
    * @param  [type] $overlay   [description]
    * @param  [type] $gravity   [description]
    * @return [type]            [description]
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