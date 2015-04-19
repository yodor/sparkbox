<?php
include_once ("lib/components/renderers/cells/TableCellRenderer.php");
include_once ("lib/components/renderers/IPhotoRenderer.php");
include_once ("lib/components/TableColumn.php");

class TableImageCellRenderer extends TableCellRenderer implements IPhotoRenderer
{

  protected $bean = "";
//   const RENDER_CROP = 1;
//   const RENDER_THUMB = 2;
//
  protected $width = -1;
  protected $height = 64;
  protected $render_mode = IPhotoRenderer::RENDER_CROP;

//   protected $render_mode;
//   protected $thumb_width;
//   protected $thumb_height;

  protected $list_limit = 0;
  
  public function setListLimit($num)
  {
	  $this->list_limit = (int)$num;
  }
  
  public function setThumbnailSize($width, $height)
  {
	  $this->width=$width;
	  $this->height=$height;
  }
  public function setRenderMode($mode) {
	  $this->render_mode = $mode;
  }
  public function getRenderMode() {
	  return $this->render_mode;
  }
  public function getThumbnailWidth() {
	  return $this->width;
  }
  public function getThumbnailHeight() {
	  return $this->height;
  }

  protected $action = false;
  public function setAction(Action $action)
  {
	  $this->action=$action;
  }
  
  
  
  public function __construct(DBTableBean $bean, $render_mode=IPhotoRenderer::RENDER_CROP, $width=48, $height=-1)
  {
	  parent::__construct();

	  $this->bean = $bean;
	  $this->width = $width;
	  $this->height = $height;
	  $this->render_mode = $render_mode;
	  $this->list_limit = 1;
  }


  
  public function renderCell($row, TableColumn $tc)
  {
      $this->processAttributes($row, $tc);

      $this->startRender();
      $prkey = $tc->getView()->getIterator()->getPrKey();

      $photoID = -1;
	
	  $limit = " LIMIT 1";
      $list_limit = (int)$this->list_limit;
//       echo $list_limit;
      
      if ($list_limit > 0) {
		$limit = " LIMIT $list_limit";
      }
      else {
		$limit = "";
      }
      
      $order_by = "";
      if ($this->bean->haveField("position")) {
		$order_by = " ORDER BY position ASC ";
      }
      
      
      $num = $this->bean->startIterator("WHERE $prkey=".$row[$prkey]." ".$order_by." ".$limit);
//       echo $this->bean->getLastIteratorSQL();
      
      if ($num>1) {
		echo "<div class='TableCellImageList' $prkey='".$row[$prkey]."' count='$num'>";
      }
      while ($this->bean->fetchNext($pfrow)) {
		  $photoID=$pfrow[$this->bean->getPrKey()];
      

		  $img_tag = "";

		  $width = $this->width;
		  $height = $this->height;

		  $blob_field = $tc->getFieldName();

		  echo "<div class='TableCellImageItem' photoID='$photoID'>";

		  if ($this->render_mode == IPhotoRenderer::RENDER_CROP) {
			$img_tag = "<img src='".SITE_ROOT."storage.php?cmd=image_crop&height=$height&width=$width&class=".get_class($this->bean)."&id=$photoID&blob_field=$blob_field'>";
		  }
		  else if ($this->render_mode == IPhotoRenderer::RENDER_THUMB) {

			$size = max($width, $height);
			
			$img_tag = "<img src='".SITE_ROOT."storage.php?cmd=image_thumb&size=$size&class=".get_class($this->bean)."&id=$photoID&blob_field=$blob_field'>";
		  }

		  if ($this->action instanceof EmptyAction) {
			  echo  $img_tag;
		  }
		  else {
			  if ($this->action ) {
				$href=$this->action->getHref($row);
				echo "<a href='$href'>$img_tag</a>";
			  }
			  else {

				echo "<a class='image_popup' href='".SITE_ROOT."storage.php?cmd=gallery_photo&class=".get_class($this->bean)."&id=$photoID&blob_field=$blob_field'  >";
				echo $img_tag;
				echo "</a>";
			  }
		  }

		  
		  echo "</div>"; //TableCellImageItem
      
      }
      
	  if ($num>1) {
		  echo "</div>"; //TableCellImageList
	  }

      $this->finishRender();
  }
}

?>