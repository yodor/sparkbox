<?php
include_once("lib/input/validators/UploadDataValidator.php");
include_once("lib/storage/ImageStorageObject.php");
include_once("lib/utils/ImageResizer.php");


class ImageUploadValidator extends UploadDataValidator
{

  private $resize_width=0;
  private $resize_height=0;

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

  protected function processUploadData(InputField $field)
  {

	  $image_storage = new ImageStorageObject($field->getValue());

	  $this->processImage($image_storage);

	  $field->setValue($image_storage);

  }
  
  public function processImage(ImageStorageObject $image_storage)
  {

      
      debug("----------------------------------------------------------------------");
      
      debug("ImageUploadValidator::processImage: UID: ".$image_storage->getUID());


      $dst_width = $this->resize_width;
      $dst_height = $this->resize_height;

      $scale = 1;

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
	  if (IMAGE_UPLOAD_UPSCALE_ENABLED) {
	    debug("UPSCALE_ENABLED");
	    $scale = 1;
	  }
	  else {
	    debug("UPSCALE_DISABLED");
	  }
      }

      $n_width = $image_storage->getWidth() * $scale;
      $n_height = $image_storage->getHeight() * $scale;
	      
      if ($n_width<1)$n_width=1;
      if ($n_height<1)$n_height=1;

      debug("ImageUploadValidator::Original Image Size:(".$image_storage->getWidth().",".$image_storage->getHeight().")");
      debug("ImageUploadValidator::MIME: ".$image_storage->getMIME());
      debug("ImageUploadValidator::Scale: ".$scale);
      debug("ImageUploadValidator::New Image Size:($n_width, $n_height) | ".memory_get_usage(true));

      $source = imagecreatefromstring($image_storage->getData());
      if (!is_resource($source))throw new Exception("source parameter is not an image resource");

      if ($n_width!= $image_storage->getWidth() || $n_height != $image_storage->getHeight()) {
	$photo = imagecreatetruecolor($n_width, $n_height);
	imagealphablending($photo, false);
	
	
	// Resize
	imagecopyresampled($photo, $source, 0, 0, 0, 0, $n_width, $n_height, $image_storage->getWidth(), $image_storage->getHeight());
	@imagedestroy($source);
	$source = $photo;
	
      }

      ob_start();

      if (strcmp(strtolower($image_storage->getMIME()),ImageResizer::TYPE_PNG)===0) {
	$image_storage->setMIME(ImageResizer::TYPE_PNG);
	
	debug("ImageUploadValidator:: Output Format is PNG");
	
	imagesavealpha($source, true);

	imagepng($source);
	
      }
      else {
	$image_storage->setMIME(ImageResizer::TYPE_JPEG);
	
	debug("ImageUploadValidator:: Default Output Format set to JPEG");
	imagejpeg($source, NULL, 95);
	
      }
      
      // pass output to image_storage
      $image_storage->setData(ob_get_contents());
      

      // end capture
      ob_end_clean();

      @imagedestroy($source);
//       @imagedestroy($photo);
      
      debug("----------------------------------------------------------------------");
  }

}

?>