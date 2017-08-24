<?php
include_once("lib/pages/AdminPageLib.php");
include_once("lib/components/MenuBarComponent.php");

include_once("lib/components/renderers/cells/BeanFieldCellRenderer.php");
include_once("lib/components/renderers/cells/CallbackTableCellRenderer.php");
include_once("lib/components/renderers/cells/BooleanFieldCellRenderer.php");

class AdminPage extends AdminPageLib
{

    public function __construct() 
    {
        parent::__construct();       
        MenuItem::$icon_path = LIB_ROOT."images/admin/spark_icons/";   
        
        $this->addMeta("viewport", "width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0");
    }
    
    protected function dumpJS()
    {
        parent::dumpJS();
    }

    protected function dumpCSS()
    {
        parent::dumpCSS();
        echo '<link rel="stylesheet" href="'.SITE_ROOT.'css/admin.css" type="text/css">';
        
    }

}

?>
