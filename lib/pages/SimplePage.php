<?php
include_once("lib/pages/SitePage.php");
include_once("lib/handlers/RequestController.php");
include_once("lib/components/renderers/IHeadRenderer.php");
include_once("lib/components/renderers/IFinalRenderer.php");
include_once("lib/beans/ConfigBean.php");

include_once("lib/panels/MessageDialog.php");

class SimplePage extends SitePage
{

	protected $auth = NULL;
	protected $login_url = "";
	protected $preferred_title = "";

	protected $page_title = "";
	
	protected $caption = "";
	
	protected $final_components = array();
	protected $head_components = array();
	
	protected function dumpMetaTags() 
	{
	    parent::dumpMetaTags();

	    echo '<meta name="audience" content="All">';
	    echo '<meta name="page-topic" content="">';
	    echo '<meta name="revisit-after" content="1 days">';
	    echo '<meta name="author" content="">';

	    echo '<meta name="robots" content="index, follow">';
	    echo '<meta http-equiv="imagetoolbar" content="no">';

	
	    if (DB_ENABLED) {
	      $config = ConfigBean::factory();
	      $config->setSection("seo");

	      $keywords = $config->getValue("meta_keywords");
	      if ($keywords) {
		echo "<meta name='keywords' content='$keywords'>";
		echo "\n";
	      }

	      $description = $config->getValue("meta_description");
	      if ($description) {
		echo "<meta name='description' content='$description'>";
		echo "\n";
	      }
	    }
	    
	    $hcmp_merged = array();
	    
	    foreach ($this->head_components as $idx=>$cmp) {
		    $hcmp_merged[$cmp->getHeadClass()] = $cmp;
		    
	    }
	    $this->head_components = $hcmp_merged;
	    
	    echo "<link rel='shortcut icon' href='".SITE_URL."/favicon.ico'>";

        }
        protected function headStart() 
        {
	    parent::headStart();
	    
	   
        }
        public function addFinalComponent(IFinalRenderer $cmp)
        {	
	    $this->final_components[] = $cmp;
        }
        public function addHeadComponent(IHeadRenderer $cmp)
        {
	    $this->head_components[] = $cmp;
	    
        }
	public function setCaption($caption)
	{
	  $this->caption = $caption;
	}
	public function getCaption()
	{
	  return $this->caption;
	}
	protected function dumpCSS()
	{
		parent::dumpCSS();


		echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/popups.css' type='text/css' >";
		echo "\n";

		//local stylesheet
		echo "<link rel='stylesheet' href='".SITE_ROOT."css/site_style.css' type='text/css' >";
		echo "\n";
		

		foreach ($this->head_components as $idx=>$cmp) {
		    $cmp->renderStyle();
// 		    echo "<!-- Head Components $idx: ".get_class($cmp)."-->";
		}
	

		
		if (is_callable("dumpCSS")) {
			dumpCSS();
		}
		
		
		   

	}
	protected function dumpJS()
	{
		parent::dumpJS();
		global $left, $right;
		
?>
<script type='text/javascript'>
var SITE_ROOT = "<?php echo SITE_ROOT;?>";
var ajax_loader="<div class='AjaxLoader'></div>";
var ajax_loader_src = SITE_ROOT+"lib/images/ajax-loader.gif";
var left = "<?php echo $left;?>";
var right = "<?php echo $right;?>";
</script>
<?php


		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/jquery-1.8.0.min.js'></script>";
		echo "\n";

		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/utils.js'></script>";
		echo "\n";
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/ajax.js'></script>";
		echo "\n";
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/JSONRequest.js'></script>";

		echo "\n\n";

		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/tooltip.js'></script>";
		echo "\n";
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/ModalPopup.js'></script>";
		echo "\n";


		echo "\n";
		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/GalleryView.js'></script>";
		echo "\n";

		echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/input.js'></script>";
		echo "\n";

// 		
		foreach ($this->head_components as $idx=>$cmp) {
		    $cmp->renderScript();
// 		    echo "<!-- Head Components $idx: ".get_class($cmp)."-->";
		}
// 		echo "<!-- Head Components End -->";
		
		
		if (is_callable("dumpJS")) {
		      dumpJS();
		}
	}

	public function __construct()
	{

	    parent::__construct();

	    if ($this->auth) {
		$context = $this->auth->getAuthContext();
		if (!$this->auth->checkAuthState())
		{
		    if (isset($_GET["ajax"])) {
			    throw new Exception("Your session is expired.");
		    }
		    else {
		      if (!isset($_SESSION[$context]["login_redirect"])){
			    $_SESSION[$context]["login_redirect"]=$_SERVER['REQUEST_URI'];
		      }
		      //if (!isset($_COOKIE["login_redirect"])){
		      //	$expire = time() + 60 * 60 * 24 * 365; // set expire to one year
		      //	setcookie("login_redirect", $_SERVER['REQUEST_URI'], $expire, "/", COOKIE_DOMAIN);
		      //}
		    }
		    header("Location: ".$this->login_url);
		    exit;

		}

		if (isset($_SESSION[$context]["login_redirect"])) {
		    header("Location: ".$_SESSION[$context]["login_redirect"]);
		    unset($_SESSION[$context]["login_redirect"]);
		    exit;
		}
	    }

	    $dialog = new MessageDialog();

	}

	public function setAuthenticator(Authenticator $auth, $login_url)
	{
		 $this->auth = $auth;
		 $this->login_url = $login_url;
	}

	public function setPreferredTitle($page_title)
	{
		$this->preferred_title = $page_title;
	}

	public function obCallback($buffer)
	{
// 		if (strlen($this->preferred_title)>0) {

		$buffer = preg_replace('#(<title.*?>).*?(</title>)#', "<title>".$this->preferred_title.TITLE_PATH_SEPARATOR.SITE_TITLE."</title>", $buffer);

// 		}
		return $buffer;
	}

	public function beginPage()
	{
		RequestController::processAjaxHandlers();

		ob_start(array($this, 'obCallback'));

		$this->htmlStart();
		$this->headStart();
		$this->headEnd();

		echo "\n<!--beginPage SimplePage-->\n";

		$this->bodyStart();

		RequestController::processRequestHandlers();
	}


	public function finishPage()
	{

		$this->processMessages();

		$this->processFinalComponents();
		
		$this->bodyEnd();

		echo "\n<!--finishPage SimplePage-->\n";

		$this->htmlEnd();

		ob_end_flush();


	}
	protected function processFinalComponents()
	{
	    foreach ($this->final_components as $idx=>$cmp) {
		$cmp->renderFinal();
	    }
	}
	protected function processMessages()
	{
	    if (Session::get("alert",false)) {
	      $alert = Session::get("alert");
?>
<script type='text/javascript' >
addLoadEvent(function(){
    showAlert(<?php echo json_string($alert);?>);
});
</script>
<?php
	      Session::clear("alert");
	      
	    }
	    echo "</div>";
	}

}

?>