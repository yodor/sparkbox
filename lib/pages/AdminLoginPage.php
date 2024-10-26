<?php
include_once("pages/SparkPage.php");

class AdminLoginPage extends SparkPage
{

    public function __construct()
    {
        parent::__construct();

        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminLoginPage.css");

        $this->setTitle(tr("Login"));
    }

}

?>
