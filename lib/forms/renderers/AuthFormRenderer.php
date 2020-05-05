<?php
include_once("lib/forms/renderers/FormRenderer.php");


class AuthFormRenderer extends FormRenderer
{

    protected $auth = NULL;

    protected $username;
    protected $password;

    public $forgot_password_url = "";

    public $fbLoginEnabled = false;

    public function __construct()
    {
        parent::__construct();
        $this->submit_button->setName("cmd");
        $this->submit_button->setAttribute("value", "doLogin");
        $this->submit_button->setAttribute("action", "login");

        $this->submit_button->setText("Login");

        $this->setClassName("FormRenderer");
        $this->setLayout(FormRenderer::FIELD_VBOX);

        $this->setAttribute("name", "AuthForm");

    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/md5.js";
        $arr[] = SITE_ROOT . "lib/js/AuthForm.js";
        return $arr;
    }

    public function startRender()
    {
        parent::startRender();

    }

    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var auth_form = new AuthForm();
                auth_form.attachWith("<?php echo $this->getAttribute("name");?>");
            });
        </script>
        <?php
    }

    public function renderSubmitLine(InputForm $form)
    {
        // 		echo "<tr>";
        // 		echo "<td class='submit_line' >";
        //
        echo "<div class='submit_line'>";

        echo "<div class='forgot_password'>";
        echo "<span>" . tr("Забравена Парола ?") . "</span>";
        echo "<br>";
        $forgot_link = "forgot_password.php";
        if (strlen($this->forgot_password_url) > 0) {
            $forgot_link = $this->forgot_password_url;
        }
        echo "<a href='$forgot_link'>" . tr("Натисни Тук") . "</a>";
        echo "</div>";

        $this->submit_button->render();


        if ($this->fbLoginEnabled) {
            echo "<BR>";

            echo "<div class='fb-login-button' onlogin='Facebook_login()' autologoutlink='false' scope='email,user_interests,user_about_me'>Login with Facebook</div>";
        }

        echo "</div>";

    }

}

?>