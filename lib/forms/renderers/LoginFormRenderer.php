<?php
include_once("forms/renderers/FormRenderer.php");


class LoginFormRenderer extends FormRenderer
{

    public $forgot_password_url = "";

    public $fbLoginEnabled = false;

    /**
     * @var AuthenticatorRequestHandler
     */
    protected $handler;
    protected $actionRenderer;

    public function __construct(LoginForm $form, AuthenticatorRequestHandler $handler)
    {
        parent::__construct($form);

        $this->handler = $handler;

        $this->setLayout(FormRenderer::FIELD_VBOX);

        $this->submitButton->setName($handler::KEY_COMMAND);
        $this->submitButton->setValue($handler->getCommandName());

        $this->submitButton->setAttribute("action", "login");
        $this->submitButton->setText("Login");

        $this->setAttribute("autocomplete", "on");


        $this->actionRenderer = new ActionRenderer(new Action("Forgot Password", "forgot_password.php"));

        $this->getTextSpace()->append($this->actionRenderer);

    }


    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/md5.js";
        $arr[] = SPARK_LOCAL . "/js/LoginForm.js";
        return $arr;
    }
    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL."/css/LoginForm.css";
        return $arr;
    }

    public function startRender()
    {
        $this->form->getInput("rand")->setValue($this->handler->getAuthenticator()->createLoginToken());

        parent::startRender();
    }

    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var auth_form = new LoginForm();
                auth_form.attachWith("<?php echo $this->form->getName();?>");
            });
        </script>
        <?php
    }

    public function renderSubmitLine()
    {
        $this->submitLine->render();

        //echo "<div class='fb-login-button' onlogin='Facebook_login()' autologoutlink='false' scope='email,user_interests,user_about_me'>Login with Facebook</div>";

    }

}

?>