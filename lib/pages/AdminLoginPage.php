<?php
include_once("lib/pages/SparkPage.php");

class AdminLoginPage extends SparkPage
{

    public function __construct()
    {
        parent::__construct();

        $this->addMeta("viewport", "width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0");

        $this->addCSS(SITE_ROOT . "lib/css/admin.css",false);
        $this->addCSS(SITE_ROOT . "lib/css/admin_buttons.css", false);
    }

    public function startRender()
    {
        parent::startRender();
        echo "<table width=100% height=100%>";
        echo "<tr><td valign=middle align=center>";
    }

    public function finishRender()
    {
        echo "</tr></td></table>";
        parent::finishRender();
    }

}

?>
