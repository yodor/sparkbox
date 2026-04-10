<?php
include_once("forms/renderers/FormRenderer.php");
include_once("components/InlineScript.php");

class LoginFormScript extends InlineScript implements IPageComponent
{
    protected LoginForm $form;

    public function __construct(LoginForm $form)
    {
        parent::__construct();

        $this->enableOnPageLoad();
        $this->form = $form;

        $code = <<<JS
let auth_form = new LoginForm();
auth_form.setName("{$this->form->getName()}");
auth_form.initialize();
JS;
        $this->setCode($code);
    }

}
class LoginFormRenderer extends FormRenderer
{

    const string ACTION_LOGIN = "login";
    const string ACTION_PASSWORD = "password";

    /**
     * @param LoginForm $form
     * @param string $responderName AuthenticatorResponder::clsas
     * @param string $token hmac challenge token
     * @throws Exception
     */
    public function __construct(LoginForm $form, string $responderName, string $token)
    {
        parent::__construct($form);

        $this->form->getInput("token")->setValue($token);

        $this->setClassName("LoginFormRenderer");

        $this->setLayout(FormRenderer::LAYOUT_VBOX);

        $this->submitButton->setName(RequestResponder::KEY_COMMAND);
        $this->submitButton->setValue($responderName);

        $this->submitButton->setAction(LoginFormRenderer::ACTION_LOGIN);
        $this->submitButton->setContents(tr("Login"));

        $this->setAttribute("autocomplete", "on");

        $action = new Action();
        $action->setAction(LoginFormRenderer::ACTION_PASSWORD);
        $action->setContents(tr("Forgot Password?"));
        $action->getURL()->fromString("forgot_password.php");

        $this->getTextSpace()->items()->append($action);

        new LoginFormScript($this->form);
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/LoginForm.js";
        return $arr;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/LoginForm.css";
        return $arr;
    }

}