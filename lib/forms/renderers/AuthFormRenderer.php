<?php
include_once("lib/forms/renderers/FormRenderer.php");


class AuthFormRenderer extends FormRenderer implements IHeadRenderer
{

	protected $auth_context = FALSE;

	protected $username;
	protected $password;


	public $fbLoginEnabled = false;

	public function __construct()
	{
		parent::__construct();
		$this->submit_button->setName("cmd");
		$this->submit_button->setAttribute("value", "doLogin");
		$this->submit_button->setAttribute("action", "login");
		
		$this->submit_button->setText("Login");

// 		$this->attributes["onSubmit"]="return checkLogin();";
		
		$this->setClassName("FormRenderer");
		$this->setFieldLayout(FormRenderer::FIELD_VBOX);
		
		$this->setAttribute("name", "AuthForm");
		
	}
	public function renderScript()
	{
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/md5.js'></script>";
		echo "\n";
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/AuthForm.js'></script>";
		echo "\n";
	}
	public function renderStyle()
	{
	
	}
// 	CONTEXT_ADMIN
	public function setAuthContext($auth_context)
	{
		$this->auth_context = $auth_context;
	}

	public function startRender()
	{
		parent::startRender();
		$rand = rand();
		$_SESSION[$this->auth_context]["rand"]=$rand;
		$this->form->getField("rand")->setValue($rand);
	}

	public function finishRender()
	{
		parent::finishRender();
?>
<script type='text/javascript'>
addLoadEvent(function(){
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
		echo "<span>".tr("Forgotten Your Password ?")."</span>";
		echo "<br>";
		echo "<a href='forgot_password.php'>".tr("Click Here")."</a>";
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