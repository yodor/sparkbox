<?php
include_once("lib/pages/SimplePage.php");


class AdminLoginPage extends SimplePage
{


    protected function dumpCSS()
    {
	parent::dumpCSS();
	echo '<link rel="stylesheet" href="'.SITE_ROOT.'lib/css/admin.css" type="text/css" >';
	echo '<link rel="stylesheet" href="'.SITE_ROOT.'lib/css/admin_buttons.css" type="text/css" >';
    }
    protected function dumpJS()
    {
	parent::dumpJS();
	
    }

    public function __construct() 
    {

	parent::__construct();
        $this->addMeta("viewport", "width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0");
    }


    public function beginPage()
    {
	parent::beginPage();
	echo "<table width=100% height=100%>";
	echo "<tr><td valign=middle align=center>";

    }

    public function finishPage()
    {

	echo "</tr></td></table>";
	parent::finishPage();

    }

}
?>
