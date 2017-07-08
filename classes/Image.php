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
        /* Adjust Brightness */
        if(isset($options['brightness'])) {
            $this->adjustBrightness($options['brightness']);
        }

        /* Adjust Blur */
        if(isset($options['blur'])) {
            $this->blurImage($options['blur']);
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


        // if(in_array($state, $validStates))
        // {
        //     $utils->generateOverlay($image, $state, $width);
        // }

        // if(in_array($overlay, $validOverlays))
        // {
        //     $utils->generateOverlay($image, $overlay, $width);
        // }


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
    * @param  [type] $state [description]
    * @param  [type] $width [description]
    * @return [type]        [description]
    */
    private function generateOverlay($image, $state, $width) {
        $overlay = new Imagick();
        $overlay->readImage('./overlays/'.$state.'.png');
        $overlay->scaleImage($width, 0);
        $image->compositeImage($overlay, imagick::COMPOSITE_OVER, 0, 0);
    }

}

?>