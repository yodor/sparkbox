<?php
include_once("forms/renderers/FormRenderer.php");
include_once("components/PageScript.php");

class LoginFormScript extends PageScript
{
    protected LoginForm $form;

    public function __construct(LoginForm $form)
    {
        parent::__construct();
        $this->form = $form;
    }

    public function code() : string {
        return <<<JS
        onPageLoad(function () {
                let auth_form = new LoginForm();
                auth_form.setName("{$this->form->getName()}");
                auth_form.initialize();
        });
JS;
    }
}
class LoginFormRenderer extends FormRenderer
{

    /**
     * @var AuthenticatorResponder
     */
    protected AuthenticatorResponder $responder;

    const string ACTION_LOGIN = "login";
    const string ACTION_PASSWORD = "password";

    public function __construct(LoginForm $form, AuthenticatorResponder $responder)
    {
        parent::__construct($form);

        $this->setClassName("LoginFormRenderer");
        //TODO
        $this->responder = $responder;

        $this->setLayout(FormRenderer::LAYOUT_VBOX);

        $this->submitButton->setName(RequestResponder::KEY_COMMAND);
        $this->submitButton->setValue($responder->getName());

        $this->submitButton->setAction(LoginFormRenderer::ACTION_LOGIN);
        $this->submitButton->setContents(tr("Login"));

        $this->setAttribute("autocomplete", "on");

        $action = new Action();
        $action->setAction(LoginFormRenderer::ACTION_PASSWORD);
        $action->setContents(tr("Forgot Password?"));
        $action->getURL()->fromString("forgot_password.php");

        $this->getTextSpace()->items()->append($action);

        new LoginFormScript($form);
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/md5.js";
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/LoginForm.js";
        return $arr;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/LoginForm.css";
        return $arr;
    }

    /**
     * TODO:
     * @return void
     * @throws Exception
     */
    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->form->getInput("rand")->setValue($this->responder->getAuthenticator()->createLoginToken());
    }

}