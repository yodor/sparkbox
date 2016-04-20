<?php

class StorageItem {

  public $itemID = -1;
  public $itemClass = "";
  
  public function hrefGallery()
  {
	  return STORAGE_HREF."?cmd=gallery_photo&id={$this->itemID}&class={$this->itemClass}";
  }
  public function hrefCrop($width, $height)
  {
	  return STORAGE_HREF."?cmd=image_crop&width=$width&height=$height&id={$this->itemID}&class={$this->itemClass}";
  }
  public function hrefThumb($width)
  {
	  return STORAGE_HREF."?cmd=image_thumb&width=$width&height=$width&id={$this->itemID}&class={$this->itemClass}";
  }
  public function hrefFile()
  {
	  return STORAGE_HREF."?cmd=data_file&id={$this->itemID}&class={$this->itemClass}";
  }
}
?>
