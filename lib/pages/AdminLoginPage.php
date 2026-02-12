<?php
include_once("pages/SparkPage.php");
include_once("auth/AdminAuthenticator.php");
include_once("responders/AuthenticatorResponder.php");

include_once("forms/LoginForm.php");
include_once("forms/renderers/LoginFormRenderer.php");

class AdminLoginPage extends SparkPage
{



    public function __construct()
    {
        parent::__construct();

        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/AdminLoginPage.css");
        $this->setComponentClass("AdminLoginPage");
    }

    public function initialize() : void
    {

        $this->setTitle(tr("Login"));

        $responder = new AuthenticatorResponder(new AdminAuthenticator());
        $responder->setCancelUrl(Spark::Get(Config::ADMIN_LOCAL) . "/login.php");
        $responder->setSuccessUrl(Spark::Get(Config::ADMIN_LOCAL) . "/index.php");


        $form = new LoginForm();

        $afr = new LoginFormRenderer($form, $responder);
        $afr->setCaption(Spark::Get(Config::SITE_TITLE) . "<BR><small>" . tr("Administration") . "</small>");

        $this->items()->append($afr);

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Expires: 0");
    }

}