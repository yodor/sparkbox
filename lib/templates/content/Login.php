<?php
include_once("templates/TemplateContent.php");
include_once("responders/AuthenticatorResponder.php");
include_once("forms/LoginForm.php");
include_once("forms/renderers/LoginFormRenderer.php");

class Login extends TemplateContent
{

    public function __construct()
    {
        parent::__construct();
    }

    public function initialize(): void
    {
        SparkPage::Instance()->setTitle($this->getContentTitle());

        $authenticator = Module::Active()->getAuthenticator();
        if (is_null($authenticator)) throw new Exception("No module Authenticator initialized");

        $responder = new AuthenticatorResponder($authenticator);
        $responder->setCancelUrl(Module::PathURL("login"));
        $responder->setSuccessUrl(Module::PathURL(""));

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Expires: 0");


        $lfr = new LoginFormRenderer(new LoginForm(), $responder);
        $action = $lfr->getTextSpace()->items()->getByAction(LoginFormRenderer::ACTION_PASSWORD);
        if ($action instanceof Action) {
            $action->setURL(Module::PathURL("password"));
        }
        $this->cmp = $lfr;
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return "Login - ".Spark::Get(Config::SITE_TITLE);
    }

}