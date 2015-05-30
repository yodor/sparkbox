<?php

include_once("lib/components/renderers/cells/TableImageCellRenderer.php");

class ProductPhotoCellRenderer extends TableImageCellRenderer
{
	public function __construct($render_mode=IPhotoRenderer::RENDER_CROP, $width=48, $height=-1)
	{
		parent::__construct(NULL, $render_mode, $width, $height);
		
	}
	protected function constructItems($row, TableColumn $tc)
	{
		$this->items = array();
		
		$item = NULL;
		
		if (isset($row["pclrpID"]) && $row["pclrpID"]>0) {
			  $item = new ImageItem();
			  $item->item_id = (int)$row["pclrpID"];
			  $item->item_class = ProductColorPhotosBean::class;
		}
		else if (isset($row["ppID"]) && $row["ppID"]>0){
			  $item = new ImageItem();
			  $item->item_id = (int)$row["ppID"];
			  $item->item_class = ProductPhotosBean::class;
		}	
		
		$this->items[] = $item;
	}
}
?>