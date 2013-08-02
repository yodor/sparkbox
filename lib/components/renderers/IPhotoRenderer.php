<?php

interface IPhotoRenderer
{
  const RENDER_CROP = 1;
  const RENDER_THUMB = 2;

  
  public function setThumbnailSize($width, $height);

  public function setRenderMode($mode);

  public function getRenderMode();

  public function getThumbnailWidth() ;
	
  public function getThumbnailHeight();
}

?>