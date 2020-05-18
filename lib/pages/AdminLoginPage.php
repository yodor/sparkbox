<?php
include_once("pages/SparkPage.php");

class AdminLoginPage extends SparkPage
{

    public function __construct()
    {
        parent::__construct();

        $this->addCSS(SPARK_LOCAL . "/css/AdminPage.css");
        $this->addCSS(SPARK_LOCAL . "/css/AdminButtons.css");
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
