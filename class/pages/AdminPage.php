<?php
include_once("lib/pages/AdminPageLib.php");
include_once("lib/components/MenuBarComponent.php");

include_once("lib/components/renderers/cells/BeanFieldCellRenderer.php");
include_once("lib/components/renderers/cells/CallbackTableCellRenderer.php");
include_once("lib/components/renderers/cells/BooleanFieldCellRenderer.php");

class AdminPage extends AdminPageLib
{
	public $caption = "";

	
    protected function dumpCSS()
    {
	parent::dumpCSS();


    }
    protected function dumpJS()
    {
	parent::dumpJS();


    }

    protected function initMainMenu()
    {
	

	$mnu = array();

	$mnu[] = new MenuItem("Content", ADMIN_ROOT."content/index.php", "class:icon_content");

	$mnu[] = new MenuItem("Settings", ADMIN_ROOT."settings/index.php", "class:icon_settings");

	return $mnu;
	
    }

	public function renderPageCaption($str=null)
    {
		if ($this->caption) {
		  parent::renderPageCaption($this->caption);
		}
		else {
		  parent::renderPageCaption($str);
		}
    }

}

?>