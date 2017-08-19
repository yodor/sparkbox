<?php
include_once("lib/input/validators/UploadDataValidator.php");
include_once("lib/storage/ImageStorageObject.php");
include_once("lib/utils/ImageResizer.php");


class ImageUploadValidator extends UploadDataValidator
{

  private $resize_width=0;
  private $resize_height=0;
  private $resize_enabled = true;
  
  public function __construct()
  {
	  parent::__construct();

	  $accept_mimes = array(
	  "image/jpeg",
	  "image/jpg",
	  "image/png",
	  "image/gif",
	  "application/octet-stream"
	  );
	  $this->setAcceptMimes($accept_mimes);

  }

  public function setResizedSize($width, $height)
  {
	  $this->resize_width=$width;
	  $this->resize_height=$height;
  }
  public function setResizeEnabled($mode)
  {
      $this->resize_enabled = ((int)$mode>0)?true:false;
  }
  protected function processUploadData(InputField $field)
  {

          debug(get_class($this)."::processUploadData() field[".$field->getName()."]");
          
          
	  $image_storage = new ImageStorageObject($field->getValue());

	  $this->processImage($image_storage);

	  $field->setValue($image_storage);

  }
  
  public function processImage(ImageStorageObject $image_storage)
  {

      
      debug("---------".get_class($this)."::processImage()");
      
      debug("UID: ".$image_storage->getUID());


      

      $scale = 1;

      if ($this->resize_enabled) {
      
	$dst_width = $this->resize_width;
	$dst_height = $this->resize_height;
      
	if ($dst_width>0 || $dst_height>0) {

	  if ($dst_width>0 && $dst_height>0) {
	  
	    debug("Resize Width Requested: ($dst_width, $dst_height)");

	  }
	  else if ($dst_width>0) {
	  
	      $ratio = (float)$image_storage->getWidth() / $dst_width;
	      debug("Autofit for Width Ratio: $ratio ");
	      $dst_height = $image_storage->getHeight() / $ratio;
	      debug("Autofit for Width: ($dst_width, $dst_height)");

	  }
	  else if ($dst_height>0) {
	  
	      $ratio = (float)$image_storage->getHeight() / $dst_height;
	      debug("Autofit for Height Ratio: $ratio ");
	      
	      $dst_width = $image_storage->getWidth() / $ratio;
	      debug("Autofit for Height: ($dst_width, $dst_height)");
	  }
	  
	  $scale = min( $dst_width/$image_storage->getWidth(), $dst_height/$image_storage->getHeight() );
	  
	}
	else if (IMAGE_UPLOAD_DEFAULT_WIDTH>0 && IMAGE_UPLOAD_DEFAULT_HEIGHT>0) {
	
	  debug("DEFAULT_UPLOAD Size Requested: (".IMAGE_UPLOAD_DEFAULT_WIDTH.",".IMAGE_UPLOAD_DEFAULT_HEIGHT.")");
	  
	  $scale = min( IMAGE_UPLOAD_DEFAULT_WIDTH/$image_storage->getWidth(), IMAGE_UPLOAD_DEFAULT_HEIGHT/$image_storage->getHeight() );

	  
	}

	if ($scale > 1 ) {
	    if (IMAGE_UPLOAD_UPSCALE) {
	      debug("IMAGE_UPLOAD_UPSCALE is true. Upscaling is enabled.");
	    }
	    else {
	      debug("IMAGE_UPLOAD_UPSCALE is false. Upscaling is disabled.");
	      //force 1:1 scale
	      $scale = 1;
	    }
	}

      }//resize_enabled
      else {
	  debug("Scaling/Resizing is disabled for 'this' validator");
      }
      
      $n_width = $image_storage->getWidth() * $scale;
      $n_height = $image_storage->getHeight() * $scale;
	      
      if ($n_width<1)$n_width=1;
      if ($n_height<1)$n_height=1;

      debug("Original Image Size:(".$image_storage->getWidth().",".$image_storage->getHeight().")");
      debug("MIME: ".$image_storage->getMIME());
      debug("Scale: ".$scale);
      debug("New Image Size:($n_width, $n_height) | Memory Usage: ".memory_get_usage(true));

      debug("Data Size: ".$image_storage->getLength());
      
      $source = false;
      
      if ($image_storage->haveData()) {
        $source = imagecreatefromstring($image_storage->getData());
      }
      else {
        $source = $image_storage->imageFromTemp();
      }
      
//       
      
      if (!is_resource($source))throw new Exception("Source parameter is not an image resource");

      //resize if needed
      if ($n_width!= $image_storage->getWidth() || $n_height != $image_storage->getHeight()) {
	$photo = imagecreatetruecolor($n_width, $n_height);
	imagealphablending($photo, false);
	
	
	// Resize
	imagecopyresampled($photo, $source, 0, 0, 0, 0, $n_width, $n_height, $image_storage->getWidth(), $image_storage->getHeight());
	@imagedestroy($source);
	
	//not a copy but reference assignment
	$source = $photo;
	
      }

      debug("Processing image data to output buffer ...");
      
      ob_start();

      if (strcmp(strtolower($image_storage->getMIME()),ImageResizer::TYPE_PNG)===0) {
	$image_storage->setMIME(ImageResizer::TYPE_PNG);
	
	debug("Output Format is PNG");
	
	imagesavealpha($source, true);

	imagepng($source);
	
      }
      else {
	$image_storage->setMIME(ImageResizer::TYPE_JPEG);
	
	debug("Output Format is JPEG");
	imagejpeg($source, NULL, 95);
	
      }
      
      // pass output to image_storage
      debug("Setting output buffer result as image data ...");
      $image_storage->setData(ob_get_contents());
    
      ob_end_clean();

      @imagedestroy($source);

      
      debug("----------------------------------------------------------------------");
  }

}

?>
