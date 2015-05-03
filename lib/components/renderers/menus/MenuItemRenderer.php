<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/IMenuItemRenderer.php");


abstract class MenuItemRenderer extends Component implements IMenuItemRenderer
{

  protected $item = NULL;


  public function __construct()
  {
	  parent::__construct();
 
  }
  

  public function renderSeparator($idx_curr, $items_total) 
  {
      if ($idx_curr < $items_total-1) {
	  echo "\n<div class='MenuSeparator' position='$idx_curr'><div></div></div>";
      }
  }

  public function setMenuItem(MenuItem $item) 
  {
	  $this->item = $item;

  }
  public function getMenuItem()
  {
	  return $this->item;
  }
  public function renderIcon()
  {

	  $icon = $this->getMenuItem()->getIcon();
	  if (strpos($icon, "class")===0){
	      list($a, $icon_class) = explode(":", $icon);
	      echo "<div class='MenuIcon $icon_class'></div>";
	  }
	  else {
	      echo "<img border=0 class='MenuIcon' src='".MenuItem::$icon_path."".$icon."'>";
	  }
  }

}
?>
