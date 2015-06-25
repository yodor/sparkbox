<?php
include_once("lib/components/renderers/menus/MenuItemRenderer.php");
include_once("lib/components/renderers/menus/SubmenuRenderer.php");

class MenuBarItemRenderer extends MenuItemRenderer
{


  protected $ir_submenu = NULL;



  public function __construct()
  {
	  parent::__construct();

	  $this->ir_submenu = new SubmenuRenderer();
  }

  public function setSubmenuRenderer(IMenuItemRenderer $ir_submenu)
  {
	  $this->ir_submenu = $ir_submenu;
  }
  public function disableSubmenuRenderer()
  {
	  $this->ir_submenu = NULL;
  }
  public function setMenuItem(MenuItem $item)
  {

	  parent::setMenuItem($item);

	  $submenu = $item->getSubmenu();

	  if (is_array($submenu) && count($submenu)>0) {
	      $this->attributes["have_submenu"]="1";
	  }
	  else {
	      if (isset($this->attributes["have_submenu"])) unset($this->attributes["have_submenu"]);
	  }
	  
	  if ($item->isSelected()) {
	      $this->attributes["active"] = 1;
	  }
	  else {
	      if (isset($this->attributes["active"])) unset($this->attributes["active"]);
	  }
  }



  public function startRender()
  {
      $attrs = $this->prepareAttributes();
      echo "<div $attrs>";
  }
  public function finishRender()
  {
      echo "</div>";
  }

  public function renderImpl()
  {
      $href = $this->item->getHref();
      $title = $this->item->getTitle();

      $target="";

      if (strlen($this->item->getTarget())>0) {
	  $target="target=\"".$this->item->getTarget()."\"";

      }

      echo "\n<a class='MenuItemLink' href='$href'  $target>";

      if ($this->getMenuItem()->getIcon()) {
		  $this->renderIcon();
      }

      if ($this->getMenuItem()->needTranslate()) {
		  $title = tr($title);
      }
      echo $title;

      
      echo "</a>";

      if (count($this->item->getSubmenu())>0) {
		  if ($this->ir_submenu){
			  echo "<div class='handle'></div>";
			  
			  $this->ir_submenu->setMenuItem($this->item);
			  $this->ir_submenu->render();
			  
			  
		  }
		  
      }
  }


}
?>
