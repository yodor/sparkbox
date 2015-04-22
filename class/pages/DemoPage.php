<?php
include_once("lib/pages/SimplePage.php");

include_once("lib/utils/MainMenu.php");
include_once("lib/components/MenuBarComponent.php");

include_once("lib/forms/InputForm.php");
include_once("lib/forms/renderers/FormRenderer.php");
include_once("lib/forms/processors/FormProcessor.php");
include_once("lib/input/InputFactory.php");



class DemoPage extends SimplePage
{

    protected $menu_bar;

    public function __construct()
    {

	parent::__construct();

	$menu = new MainMenu();

	$arr = array();
	$item = new MenuItem("Controls", SITE_ROOT."controls.php");
	$arr[] = $item;
	
	$item1 = new MenuItem("Array Controls", SITE_ROOT."array_controls.php");
	$item->addMenuItem($item1);
	$item2 = new MenuItem("AJAX Upload", SITE_ROOT."ajax_upload.php");
	$item->addMenuItem($item2);
	$item3 = new MenuItem("Upload", SITE_ROOT."upload_controls.php");
	$item->addMenuItem($item3);
	
	
	$arr[] = new MenuItem("Popups", SITE_ROOT."popups.php");
	
	$item = new MenuItem("Gallery", SITE_ROOT."gallery.php");
	$arr[] = $item;
	$item1 = new MenuItem("Styled Gallery Popup", SITE_ROOT."gallery_custom.php");
	$item->addMenuItem($item1);
	
	$arr[] = new MenuItem("MCE Image Browser", SITE_ROOT."mce_browser.php");
	
	$menus = new MenuItem("Menus", SITE_ROOT."menu.php");
	$arr[] = $menus;
	
	$db_menu = new MenuItem("DB Menu", SITE_ROOT."db_menu.php");
	$menus->addMenuItem($db_menu);
	
	$item = new MenuItem("Tree View", SITE_ROOT."tree.php");
	$arr[] = $item;
	
	$item1 = new MenuItem("Aggregate Tree", SITE_ROOT."related_tree.php");
	$item->addMenuItem($item1);
	
	$item2 = new MenuItem("Products List", SITE_ROOT."products/list.php");
	$item->addMenuItem($item2);
	
	$arr[] = new MenuItem("Fonts", SITE_ROOT."fonts.php");
	$arr[] = new MenuItem("CSS3", SITE_ROOT."css3.php");
	
	$arr[] = new MenuItem("Publications", SITE_ROOT."news.php");
	
	$menu->setMenuItems($arr);


	$this->menu_bar = new MenuBarComponent($menu);

	$this->menu_bar->setName("DemoPage");

// 	$this->menu_bar->getItemRenderer()->disableSubmenuRenderer();
	

    }


    protected function dumpCSS()
    {
	parent::dumpCSS();

	echo "<link rel='stylesheet' href='".SITE_ROOT."css/demo.css' type='text/css'>";
	echo "\n";

    }
    protected function dumpJS()
    {
	parent::dumpJS();
	echo "\n";
    }

    protected function dumpMetaTags()
	{
		parent::dumpMetaTags();
		echo "\n\n";
		echo "<meta name='viewport' content='width=960, initial-scale=0.45'>\n";
		echo "\n\n";
	}

    public function beginPage()
    {
	parent::beginPage();

	echo "\n<!--beginPage DemoPage-->\n";

	$main_menu = $this->menu_bar->getMainMenu();
	$main_menu->selectActiveMenus();

	$this->preferred_title = constructSiteTitle($main_menu->getSelectedPath());

	echo "<div align=center>";

	  echo "<div class='MenuBar'>";
	  $this->menu_bar->render();
	  echo "</div>";
	  
	  echo "<div class=clear></div>";

	  echo "<div class='main_content'>"; //inner contents

    }


    public function finishPage()
    {

// 	  echo "<div class='version_label'>";
// 	  echo "<label>";
// 	  echo "PHP: ".phpversion();
// // 	  echo " | ";
// // 	  echo "MySQL: C.".mysql_get_client_info();
// // 	  echo "/S.".mysql_get_server_info();
// 	  echo "</label>";
// 	  echo "</div>";
	  
	  echo "</div>"; //main_content
	echo "</div>"; //align=center

	echo "\n";
	echo "\n<!--finishPage DemoPage-->\n";

	parent::finishPage();


    }

}

?>