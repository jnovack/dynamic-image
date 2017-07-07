<?php

class Image {
  function __construct()
    {
    if(isset($_GET['file']) && $_GET['file'] != '')
    {
      $file = $_GET['file'];
      $utils = new Utils();
      $image = new Imagick();
      $image->readImage('../images/managed/'.$file);
      $imageMaxWidth = $image->getImageWidth();
      $maxBlur = 50;
      $width = (isset($_GET['width']) && $_GET['width'] != '' && $_GET['width'] <= $imageMaxWidth) ? $_GET['width'] : $imageMaxWidth;
      $state = (isset($_GET['state']) && $_GET['state'] != '') ? $_GET['state'] : 'active';
      $overlay = (isset($_GET['overlay']) && $_GET['overlay'] != '') ? $_GET['overlay'] : '';
      $blur = (isset($_GET['blur']) && $_GET['blur'] != '') ? ($_GET['blur'] <= $maxBlur) ? $_GET['blur'] : $maxBlur : 0;
      $quality = (isset($_GET['quality']) && $_GET['quality'] != '') ? $_GET['quality'] : 0;
      $brightness = (isset($_GET['brightness']) && $_GET['brightness'] != '' && $_GET['brightness'] <= 100) ? -(100 - $_GET['brightness']) : 0;
      $image->thumbnailImage($width, 0);
      $height = $image->getImageHeight();
      $cW = (isset($_GET['cW']) && $_GET['cW'] != '') ? $_GET['cW'] : $width;
      $cH = (isset($_GET['cH']) && $_GET['cH'] != '') ? $_GET['cH'] : $height;
      $cX = (isset($_GET['cX']) && $_GET['cX'] != '') ? $_GET['cX'] : 0;
      $cY = (isset($_GET['cY']) && $_GET['cY'] != '') ? $_GET['cY'] : 0;

      $validStates = array('soldout', 'cancelled', 'delayed');
      $validOverlays = array('disclaimer', 'disclaimertop');
      $cropYPoints = array('top','center','bottom');
      $cropXPoints = array('left','center','right');

      if($cW >= 0 || $cH >= 0)
      {
        if(!is_numeric($cX) || !is_numeric($cY))
        {
          if(in_array($cY, $cropYPoints))
          {
            switch ($cY) {
              case 'top' :
                $cY = 0;
              break;
              case 'center' :
                $cY = ($height - $cH) / 2;
              break;
              case 'bottom' :
                $cY = $height - $cH;
              break;
            }
          }

          if(in_array($cX, $cropXPoints))
          {
            switch ($cX) {
              case 'left' :
                $cX = 0;
              break;
              case 'center' :
                $cX = ($width - $cX) / 2;
              break;
              case 'right' :
                $cX = $width - $cW;
              break;
            }
          }
        }
        $utils->cropImage($image, $cW, $cH, $cX, $cY);
      }

      if($brightness != 100)
      {
        $utils->adjustBrightness($image, $brightness);
      }

      if(in_array($state, $validStates))
      {
        $utils->generateOverlay($image, $state, $width);
      }

      if(in_array($overlay, $validOverlays))
      {
        $utils->generateOverlay($image, $overlay, $width);
      }

      if($blur != 0)
      {
        $utils->blurImage($image, $blur, $width);
      }

      if($quality < 100)
      {
        $utils->compressImage($image, $quality);
      }

      header("Content-Type: image/" . $image->getImageFormat());
      header("Content-disposition: inline; filename='.$file.'");
      echo $image;

    }else{
      header("Location: https:www.parxcasino.com");
      die();
    }
    }
}

class Utils {
  /**
   * [generateOverlay description]
   * @param  [type] $image [description]
   * @param  [type] $state [description]
   * @param  [type] $width [description]
   * @return [type]        [description]
   */
  function generateOverlay($image, $state, $width)
  {
    $overlay = new Imagick();
    $overlay->readImage('../images/overlays/'.$state.'.png');
    $overlay->scaleImage($width, 0);
    $image->compositeImage($overlay, imagick::COMPOSITE_OVER, 0, 0);
  }

  /**
   * [adjustBrightness description]
   * @param  [type] $image      [description]
   * @param  [type] $brightness [description]
   * @return [type]             [description]
   */
  function adjustBrightness($image, $brightness)
  {
    $image->brightnessContrastImage($brightness, 0, 134217727);
  }

  /**
   * [blurImage description]
   * @param  [type] $image [description]
   * @param  [type] $blur  [description]
   * @param  [type] $width [description]
   * @return [type]        [description]
   */
  function blurImage($image, $blur, $width)
  {
    $image->thumbnailImage(240, 0);
    $image->blurImage(0, $blur);
    $image->thumbnailImage($width, 0);
  }

  /**
   * [cropImage description]
   * @param  [type] $image [description]
   * @param  [type] $cW    [description]
   * @param  [type] $cH    [description]
   * @param  [type] $cX    [description]
   * @param  [type] $cY    [description]
   * @return [type]        [description]
   */
  function cropImage($image, $cW, $cH, $cX, $cY)
  {
    $image->cropImage($cW, $cH, $cX, $cY);
    $height = $image->getImageHeight();
  }

  /**
   * [compressImage description]
   * @param  [type] $image   [description]
   * @param  [type] $quality [description]
   * @return [type]          [description]
   */
  function compressImage($image, $quality)
  {
    $image->setImageCompressionQuality($quality);
  }
}

$img = new Image();

?>
