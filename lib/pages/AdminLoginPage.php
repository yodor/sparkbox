<?php
include_once("pages/SparkPage.php");

class AdminLoginPage extends SparkPage
{

    public function __construct()
    {
        parent::__construct();

        $this->addMeta("viewport", "width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0");

        $this->addCSS(SPARK_LOCAL . "/css/admin.css", FALSE);
        $this->addCSS(SPARK_LOCAL . "/css/admin_buttons.css", FALSE);
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
