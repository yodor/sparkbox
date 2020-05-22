<?php
include_once ("templates/PageTemplate.php");

include_once("pages/AdminLoginPage.php");
include_once("auth/AdminAuthenticator.php");
include_once("responders/AuthenticatorResponder.php");

include_once("forms/LoginForm.php");
include_once("forms/renderers/LoginFormRenderer.php");

class AdminLogin extends PageTemplate
{
    public function __construct()
    {
        parent::__construct();

        $this->page->setTitle(tr("Administration"));

    }

    protected function initPage()
    {
        $this->page = new AdminLoginPage();
        //$this->page->addCSS(SPARK_LOCAL . "/css/LoginForm.css");

    }

    protected function initPageActions()
    {

    }

    public function initView()
    {

        $auth = new AdminAuthenticator();

        $req = new AuthenticatorResponder($auth);
        $req->setCancelUrl(LOCAL . "/admin/login.php");
        $req->setSuccessUrl(LOCAL . "/admin/index.php");

        $af = new LoginForm();

        $afr = new LoginFormRenderer($af, $req);

        $afr->setCaption(SITE_TITLE . "<BR><small>" . tr("Administration") . "</small>");

        $this->view = $afr;
        $this->append($afr);
    }

    public function startRender()
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Expires: 0");

        parent::startRender();
    }

}