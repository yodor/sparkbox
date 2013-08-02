<?php
include_once("lib/storage/FileStorageObject.php");

class ImageStorageObject extends FileStorageObject
{

  protected $width = -1;
  protected $height = -1;

  public function __construct(FileStorageObject $file_storage=NULL)
  {
	  parent::__construct();
	  if ($file_storage) {
		  
		  $this->setData($file_storage->getData());
		  $this->setUploadStatus($file_storage->getUploadStatus());
		  $this->setMIME($file_storage->getMIME());
		  $this->setTimestamp($file_storage->getTimestamp());
		  $this->setTempName($file_storage->getTempName());
		  $this->setFilename($file_storage->getFilename());
		  $this->setUID($file_storage->getUID());
// 		  $this->setPurged($file_storage->isPurged());
		  debug("ImageStorageObject::CTOR: copying FileStorageObject UID: ".$file_storage->getUID());
	  }
  }

  public function getWidth()
  {
	  return $this->width;
  }
  public function getHeight()
  {
	  return $this->height;
  }
  public function setWidth($width)
  {
	  $this->width = $width;
  }
  public function setHeight($height)
  {
	  $this->height=$height;
  }
  public function setData($data)
  {

	@$src_img = imagecreatefromstring($data);
	if ($src_img===FALSE){
		throw new Exception("Data is not holding image");
	}

	$this->width = imagesx($src_img);
	$this->height = imagesy($src_img);

	@imagedestroy($src_img);

	if ($this->width<1 || $this->height<1){
		throw new Exception("Invalid image dimensions from data");
	}

	parent::setData($data);

  }

  public function deconstruct(array &$row, $data_key="data", $doEscape=true)
  {
	parent::deconstruct($row, $data_key, $doEscape);

	$row["width"]=$this->width;
	$row["height"]=$this->height;

  }

}
?>