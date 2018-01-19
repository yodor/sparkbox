<?php
include_once("lib/pages/SimplePage.php");

include_once("lib/auth/AdminAuthenticator.php");
include_once("lib/beans/AdminAccessBean.php");

include_once("lib/handlers/ChangePositionRequestHandler.php");
include_once("lib/handlers/DeleteItemRequestHandler.php");
include_once("lib/handlers/ToggleFieldRequestHandler.php");

include_once("lib/iterators/BeanResultIterator.php");


include_once("lib/utils/ReferenceKeyPageChecker.php");
include_once("lib/utils/SelectQuery.php");


include_once("lib/components/InputFormView.php");
include_once("lib/components/TableView.php");

include_once("lib/utils/PageSessionMenu.php");

class AdminPageLib extends SimplePage
{

    public $caption = "";
	
    protected $authstore = NULL;
    protected $roles = array();

    protected $menu_bar = NULL;

    
    
    protected function initMainMenu()
    {
		global $admin_menu;
		if (!isset($admin_menu)) {
		
		  $admin_menu = array();

		  

		  $admin_menu[] = new MenuItem("Content", ADMIN_ROOT."content/index.php", "class:icon_content");

		  $admin_menu[] = new MenuItem("Settings", ADMIN_ROOT."settings/index.php", "class:icon_settings");

		  
		}
		return $admin_menu;
    }
    

    public function getAdminID()
    {
	return $this->authstore["id"];
    }

    protected function dumpCSS()
    {
	parent::dumpCSS();
	
	echo '<link rel="stylesheet" href="'.SITE_ROOT.'lib/css/admin.css" type="text/css">';echo "\n";
	echo '<link rel="stylesheet" href="'.SITE_ROOT.'lib/css/admin_buttons.css" type="text/css">';echo "\n";
	echo '<link rel="stylesheet" href="'.SITE_ROOT.'lib/css/admin_menu.css" type="text/css">';echo "\n";

    }
    protected function dumpJS()
    {
	parent::dumpJS();
	
	echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/cookies.js'></script>";
        echo "\n";


    }

    public function renderPageCaption($str=null)
    {
    
	  $caption = "";

      
      $dynmenu = $this->menu_bar->getMainMenu();
      $arr = $dynmenu->getSelectedPath();
      
      
      //default caption of page from MenuItem
      if (count($arr)>0) {
		$arr = array_reverse($arr);
		$item = $arr[0];
		if ($item instanceof MenuItem) {
		  $caption = $item->getTitle();
		}
      }
      
      	  
	  //property caption
	  if ($this->caption) {
		  $caption = $this->caption;
	  }
      else if ($str) {
		  $caption = $str;
      }
      
      
      if ($caption) {
		  echo "<div class='page_caption'>";
		  
		  
		 
		  
		  if (count($this->actions)>0) {
			$renderer = new ActionRenderer();
			echo "<div class='page_actions'>";
			$renderer->renderActions($this->actions);
			echo "</div>";
		  }
		  echo $caption;
		  echo "</div>";
      }
    }
    
    
    
    public function __construct() {

	$this->setAuthenticator(new AdminAuthenticator(), SITE_ROOT."admin/login.php");

	parent::__construct();


	$this->authstore = Session::get(CONTEXT_ADMIN);

	$adminID = (int)$this->authstore["id"];
	$b = new AdminAccessBean();
	$n = $b->startIterator("WHERE userID=$adminID");
	while ($b->fetchNext($row)){
		$this->roles[] = $row["role"];
	}

	$dynmenu = new PageSessionMenu(CONTEXT_ADMIN, $this->initMainMenu());

	
	$this->menu_bar = new MenuBarComponent($dynmenu);
	$this->menu_bar->toggle_first=false;
	$this->menu_bar->setName("admin_menu");
	$this->menu_bar->setClassName("admin_menu");
	
	$this->menu_bar->setAttribute("submenu_popup", "0");
    }


    public function haveRole($role) 
    {
	return in_array($role, $this->roles) || (count($this->roles)==0);
    }

    public function checkAccess($role, $do_redirect=true)
    {
	$ret = $this->haveRole($role);
	if (!$ret && $do_redirect) {
		header("Location: ".ADMIN_ROOT."access.php");
		exit;
	}
	return $ret;
    }

    public function renderAdminHeader()
    {
			$dynmenu = $this->menu_bar->getMainMenu();
			
// 			echo "<div class='admin_logo'></div>";
			
	    $arr = $dynmenu->getSelectedPath();
	    if (count($arr)>0) {
			echo "<div class='LocationPath'>";
			  echo "<label>".tr("Location").": </label>";
			  $act = new ActionRenderer();
			  $act->enableSeparator(true);
			  $act->renderActions($dynmenu->getSelectedPath());
			echo "</div>";
		}
			
			echo "<div class='welcome'>";
			  echo "<span class='text_admin'>Welcome, {$this->authstore["fullname"]}</span>";
			  $btn = StyledButton::DefaultButton();
			  $btn->drawButton("Logout", ADMIN_ROOT."logout.php");
			echo "</div>";


			
    }
    public function beginPage($arr_menu=array())
    {
	//allow processing of ajax handlers first
	parent::beginPage();

	
	$dynmenu = $this->menu_bar->getMainMenu();
	
	$dynmenu->update($arr_menu);

	$this->preferred_title = constructSiteTitle($dynmenu->getSelectedPath());
	
	echo "\n<!--beginPage AdminPage-->\n";
	echo "<table class='admin_layout'>";

	  echo "<tr>";
	  
		echo "<td class='admin_header' colspan=2>";

		$this->renderAdminHeader();	

		echo "</td>";
	  
	  echo "</tr>";

	  echo "<tr>";
	  
	    echo "<td class='left_menu'>";
	    

            $this->menu_bar->render();


	    if(is_callable("drawMenuPrivate")) {
	      call_user_func("drawMenuPrivate", $this);
	    }

	    echo "</td>";


// 	    $sname = str_replace(".php","",basename($_SERVER["SCRIPT_NAME"]));
// 	    $pname = basename(dirname($_SERVER["SCRIPT_NAME"]));

	    echo "<td class='page_area ".$this->getPageClass()."'>";
	    echo "\n\n";
	    

    }

    public function finishPage()
    {

	  echo "</td>";//page_area
	echo "</tr>";

	echo "<tr><td colspan=2 class='admin_footer'>";

	  echo "<span class='copy'>Copyright &copy; ".date("Y")." ".SITE_TITLE.". All Rights Reserved.</span>";
	  echo "<img class='logo' src='".SITE_ROOT."lib/images/admin/sparkbox.png'>";

	echo "</td></tr>";
	echo "</table>";

	echo "\n<!--finishPage AdminPage-->\n";
?>
<script type='text/javascript'>

// addLoadEvent(function(){
//     if (docCookies.hasItem("MenuBar.visibility")) {
//         var menu_visible = parseInt(docCookies.getItem("MenuBar.visibility"));
//         //default is menu is visible
//         if ( menu_visible == 0) {
//             if ($(".MenuBar").hasClass("normal")) {
//                 $(".MenuBar").removeClass("normal");
//             }
//         }
//         else if (menu_visible == 1) {
//             
//             $(".MenuBar").addClass("normal");
//           
//         }
//     }
//     
// });
</script>
<?php
	parent::finishPage();
    }

}

?>
