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
		  
		  
		  $this->setUploadStatus($file_storage->getUploadStatus());
		  $this->setMIME($file_storage->getMIME());
		  $this->setTimestamp($file_storage->getTimestamp());
		  $this->setTempName($file_storage->getTempName());
		  $this->setFilename($file_storage->getFilename());
		  $this->setUID($file_storage->getUID());
		  
		  //!set this last as we process tempName in set data when data is zero for uploaded files
		  $this->setData($file_storage->getData());
		  
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
        debug("ImageStorageObject::setData() Query image dimensions: Data Length: ".strlen($data));
        
        $source = false;
        
        //data is empty for during upload to limit memory usage. Use temp file name
        if (strlen($data)==0) {
            
            if (strlen($this->getTempName())==0) {
                debug("ImageStorageObject::setData() Empty Data and TempName!");
                throw new Exception("TempName and Data are empty");
            }
            
            debug("ImageStorageObject::setData() Data is empty. Trying uploaded file: ".$this->getTempName());
            
            $source = $this->imageFromTemp();
            
        }
        else {
        
            $source = @imagecreatefromstring($data);
            if (!$source){
                throw new Exception("Data is not holding image");
            }
            
        }
        
        $this->width = imagesx($source);
        $this->height = imagesy($source);

        @imagedestroy($source);

        debug("ImageStorageObject::setData() Dimensions WIDTH:{$this->width} HEIGHT:{$this->height}");
        
        if ($this->width<1 || $this->height<1){
            throw new Exception("Invalid image dimensions from data");
        }
	parent::setData($data);

  }
  public function imageFromTemp()
  {
        if (!is_uploaded_file($this->getTempName())) throw new Exception("Not an uploaded file: ".$this->getTempName());
                
        debug("ImageStorageObject::imageFromTemp() File: ".$this->getTempName()." is valid uploaded file");
        
        debug("Memory Info - memory_limit: ".ini_get("memory_limit")." | memory_usage: ".memory_get_usage());

        debug("ImageStorageObject::imageFromTemp() Trying JPEG ...");
        $source = @imagecreatefromjpeg($this->getTempName());
        if (!$source){
            debug("ImageStorageObject::imageFromTemp() Trying PNG ...");
            $source = @imagecreatefrompng($this->getTempName());
            if (!$source) throw new Exception("Data is not holding JPEG or PNG image");
        }
        return $source;
  }
  public function deconstruct(array &$row, $data_key="data", $doEscape=true)
  {
	parent::deconstruct($row, $data_key, $doEscape);

	$row["width"]=$this->width;
	$row["height"]=$this->height;

  }

}
?>
