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

    public $fbLoginEnabled = FALSE;

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

        $this->setLayout(FormRenderer::FIELD_VBOX);

        $this->submitButton->setName(RequestResponder::KEY_COMMAND);
        $this->submitButton->setValue($this->responder->getName());

        $this->submitButton->setAttribute("action", "login");
        $this->submitButton->setContents("Login");

        $this->setAttribute("autocomplete", "on");

        $this->action = new Action("Forgot Password?", "forgot_password.php");

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
        $arr[] = SPARK_LOCAL . "/js/md5.js";
        $arr[] = SPARK_LOCAL . "/js/LoginForm.js";
        return $arr;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/LoginForm.css";
        return $arr;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->form->getInput("rand")->setValue($this->responder->getAuthenticator()->createLoginToken());
    }

}

?>
