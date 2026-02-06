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

    public bool $fbLoginEnabled = FALSE;

    /**
     * @var AuthenticatorResponder
     */
    protected AuthenticatorResponder $responder;
    protected Action $action;

    public function __construct(LoginForm $form, AuthenticatorResponder $responder)
    {
        parent::__construct($form);

        $this->setClassName("LoginFormRenderer");

        $this->responder = $responder;

        $this->setLayout(FormRenderer::LAYOUT_VBOX);

        $this->submitButton->setName(RequestResponder::KEY_COMMAND);
        $this->submitButton->setValue($this->responder->getName());

        $this->submitButton->setAttribute("action", "login");
        $this->submitButton->setContents(tr("Login"));

        $this->setAttribute("autocomplete", "on");

        $this->action = new Action(tr("Forgot Password?"), "forgot_password.php");

        $this->getTextSpace()->items()->append($this->action);

        new LoginFormScript($form);
    }

    public function forgotPasswordAction() : Action
    {
        return $this->action;
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

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->form->getInput("rand")->setValue($this->responder->getAuthenticator()->createLoginToken());
    }

}

?>
