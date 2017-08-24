<?php
class ImageResizerException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
class ImageResizer
{
  public static $max_width = -1;
  public static $max_height = -1;

  public static $src_x = 0;
  public static $src_y = 0;
  
  
  public static $gray_filter = false;

  public static $n_width = -1;
  public static $n_height = -1;
  
  public static $src_width = -1;
  public static $src_height = -1;
  
  const TYPE_JPEG = "image/jpeg";
  const TYPE_PNG = "image/png";
  
  public static $output_type = ImageResizer::TYPE_JPEG;
  
  public static function clampDimension()
  {
    if (ImageResizer::$max_width>2560) {
      ImageResizer::$max_width = 2560;
    }
    if (ImageResizer::$max_height>2560) {
      ImageResizer::$max_height = 2560;
    }
    
  }
  public static function createSource(&$row)
  {
      @$source = imagecreatefromstring($row["photo"]);
      if ($source===FALSE){
	  throw new ImageResizerException("ImageResize::crop: Unrecognized Image source in row[photo]");
      }

      ImageResizer::$output_type = $row["mime"];
      return $source;
      
  }
  public static function crop(&$row)
  {
      ImageResizer::clampDimension();
      
      $dst_width = ImageResizer::$max_width;
      $dst_height = ImageResizer::$max_height;

      $source = ImageResizer::createSource($row);
      
      ImageResizer::$src_width = imagesx($source);
      ImageResizer::$src_height = imagesy($source);
      
      
      debug("ImageResizer::crop() Original Image Size:(".ImageResizer::$src_width." x ".ImageResizer::$src_height.")");
      
      $scale = 1;

      $exact_fit = false;

      if ($dst_width>0 || $dst_height>0) {

	if ($dst_width>0 && $dst_height>0) {
	
	  debug("ImageResizer::crop() Requested Exact Fit Rectangle: ($dst_width x $dst_height)");
	  $exact_fit = true;
	  
	  $scale = max( $dst_width/ImageResizer::$src_width, $dst_height/ImageResizer::$src_height );
	  
	  
	}
	else if ($dst_width>0 ) {
	
	  $ratio = (float)ImageResizer::$src_width / $dst_width;
	  $dst_height = ImageResizer::$src_height / $ratio;
	  debug("ImageResizer::crop() Requested Fit to Width: ($dst_width, $dst_height) Ratio: $ratio");
	  $scale = min( $dst_width/ImageResizer::$src_width, $dst_height/ImageResizer::$src_height );

	}
	else if ($dst_height>0 ) {
	
	  $ratio = (float)ImageResizer::$src_height / $dst_height;
	  $dst_width = ImageResizer::$src_width / $ratio;
	  debug("ImageResizer::crop() Requested Fit to Height: ($dst_width, $dst_height) Ratio: $ratio");
	  $scale = min( $dst_width/ImageResizer::$src_width, $dst_height/ImageResizer::$src_height );
	  
	}
	
	
	
      }


      $n_width = ImageResizer::$src_width * $scale;
      $n_height = ImageResizer::$src_height * $scale;
	      
	      
      if ($n_width<1)$n_width=1;
      if ($n_height<1)$n_height=1;

      ImageResizer::$n_width = $n_width;
      ImageResizer::$n_height = $n_height;


      debug("ImageResizer::crop() Calculated Output Image Dimension ($n_width x $n_height) Scale: $scale");

      //CHECK
      if ($exact_fit) {
        
	if (ImageResizer::$n_width != ImageResizer::$max_width || ImageResizer::$n_height != ImageResizer::$max_height) {
            
            debug("ImageResizer::crop() Exact fit requested");
	    
	    ImageResizer::$n_width =  ImageResizer::$max_width;
	    ImageResizer::$n_height =  ImageResizer::$max_height;
	    
	    ImageResizer::$src_width =  ImageResizer::$max_width;
	    ImageResizer::$src_height = ImageResizer::$max_height;
	    
	    //@imagedestroy($source);
	    
	    //$source = ImageResizer::createSource($row);
	    
	    ImageResizer::outputImage($row, $source, true);
	}
      }
      else {
        ImageResizer::outputImage($row, $source);
      }
      
      @imagedestroy($source);

      
  }
  
  public static function thumbnail(&$row, $size)
  {

	ImageResizer::$max_width=$size;
	ImageResizer::$max_height=$size;
	//ImageResizer::autoCrop($row);
	ImageResizer::thumbnailOld($row);
	
  }
 public static function thumbnailOld(&$row)
  {

                $size = ImageResizer::$max_width;
                
		@$src_img = imagecreatefromstring($row["photo"]);
		if ($src_img===FALSE){
			throw new Exception("Unrecognized Image File");
		}

		$width = imagesx($src_img);
		$height = imagesy($src_img);

// 		if ($width <= $size) {
// 			$new_w = $width;
// 			$new_h = $height;
// 		} else {
// 			$new_w = $size;
// 			$new_h = abs($new_w * $aspect_ratio);
// 		}
		$dstx=0;
		$dsty=0;

		if ($width>$height){
			$aspect_ratio = $width/$height;
			$new_h = $size;
			$new_w = abs($new_h * $aspect_ratio);
			$dstx= ($size-$new_w)/2;
		}
		else {
			$aspect_ratio = $height/$width;
			$new_w = $size;
			$new_h = abs($new_w * $aspect_ratio);
		}


		$img = imagecreatetruecolor($size,$size);
		imagecopyresampled($img,$src_img,$dstx,$dsty,0,0,$new_w,$new_h,$width,$height);
		imagedestroy($src_img);

		ob_start();
		if (ImageResizer::$gray_filter) {
		  imagefilter($img, IMG_FILTER_GRAYSCALE);
		}
		imagejpeg($img,NULL, 95);
		$row["photo"] = ob_get_contents();
		$row["size"] = ob_get_length();
		ob_end_clean();
		imagedestroy($img);

  }
    public static function autoCrop(&$row)
    {
	ImageResizer::clampDimension();
	
	$dst_width = ImageResizer::$max_width;
	$dst_height = ImageResizer::$max_height;

	if ($dst_width<1 || $dst_height<1) {
	    debug("ImageResizer::autoCrop requires positive non-zero max_width and max_height, returning original image.");
	    return;
	}

	$source = ImageResizer::createSource($row);
	
	$src_width = imagesx($source);
	$src_height = imagesy($source);

	
	$scale = min( $dst_width/$src_width, $dst_height/$src_height );

	$n_width = $src_width * $scale;
	$n_height = $src_height * $scale;
		
	if ($n_width<1)$n_width=1;
	if ($n_height<1)$n_height=1;

	debug("ImageResizer::autoCrop: Original Image Size:($src_width,$src_height)");
	debug("ImageResizer::autoCrop: Scale: ".$scale);
	debug("ImageResizer::autoCrop: New Image Size:($n_width, $n_height) | ".memory_get_usage(true));

	ImageResizer::$n_width = $n_width;
	ImageResizer::$n_height = $n_height;

	ImageResizer::$src_width = $src_width;
	ImageResizer::$src_height = $src_height;

	ImageResizer::outputImage($row, $source);
	
	@imagedestroy($source);
	
    }
    protected static function outputImage(&$row, &$source, $force_process=false)
    {

	$photo = NULL;
	 
	if (ImageResizer::$n_width != ImageResizer::$src_width || ImageResizer::$n_height != ImageResizer::$src_height || $force_process) {

	  $photo = imagecreatetruecolor(ImageResizer::$n_width, ImageResizer::$n_height);
	  imagealphablending($photo, false);
	  

	  // Resize
	  imagecopyresampled($photo, $source, 0, 0, 0, 0, ImageResizer::$n_width, ImageResizer::$n_height, ImageResizer::$src_width, ImageResizer::$src_height);
	  
	  @imagedestroy($source);

	  $source = $photo;
	  
	}

	
	if (ImageResizer::$gray_filter) {
	  imagefilter($source, IMG_FILTER_GRAYSCALE);
	}
	
	ob_start();

	if (ImageResizer::$output_type === ImageResizer::TYPE_PNG) {
	  
	  imagesavealpha($source, true); 
	  imagepng($source);
	}
	else {
	  imagejpeg($source, NULL, 95);
	}

	$row["photo"] = ob_get_contents();
	$row["size"] = ob_get_length();
	ob_end_clean();

    }
}
?>
