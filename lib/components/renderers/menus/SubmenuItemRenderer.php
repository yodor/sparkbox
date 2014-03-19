<?php
include_once("lib/components/renderers/menus/MenuItemRenderer.php");


class SubmenuItemRenderer extends MenuItemRenderer
{

  public function __construct()
  {
    parent::__construct();
  }

  public function renderSeparator($idx_curr, $items_total) {
// 	  if ($idx_curr < $items_total-1) {
// 		  $separator_class = $this->getClassName()."_separator";
// 		  echo "\n<div class=$separator_class></div>";
// 	  }
  }

  public function setMenuItem(MenuItem $item)
  {

	  parent::setMenuItem($item);

	  if ($item->isSelected()) {
	      $this->attributes["selected"] = 1;
	  }
	  else {
	      if (isset($this->attributes["selected"])) unset($this->attributes["selected"]);
	  }
  }

  public function renderImpl()
  {

	  $submenu = $this->getMenuItem()->getSubmenu();

	  echo "<div class='SubmenuItemOuter'>";

	  $title = $this->getMenuItem()->getTitle();
	  $href = $this->getMenuItem()->getHref();

	  $target="";
	  
	  if (strlen($this->item->getTarget())>0) {
	      $target="target=\"".$this->item->getTarget()."\"";
	  }
	  
	  echo "\n";
	  
	  echo "<a class='SubmenuItemLink' href='$href' $target >";
	  
	  if ($this->getMenuItem()->needTranslate()) {
	    $title = tr($title);
	  }
	  echo $title;
	  
	  echo "</a>";

	  if (count($this->getMenuItem()->getSubmenu())>0) {
	      echo "<div class='RightPart'>";
	      echo " > ";
	      echo "</div>";
	  }
	  
	  echo "</div>";

	  

  }

}
?>
