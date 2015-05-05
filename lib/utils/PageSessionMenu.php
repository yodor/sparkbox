<?php
include_once("lib/utils/MainMenu.php");


class PageSessionMenu extends MainMenu {

    protected $context = "DEFAULT_CONTEXT";

    
    public function __construct($context, array $main_menu)
    {
		parent::__construct();
		
		$this->context = $context;

		//assign initial menu
		$this->main_menu = $main_menu;
		
		//check if there is already a menu in session and use it instead or put the inital menu to the session
		if (isset($_SESSION[$this->context]["Menu"])) {
			$this->main_menu = unserialize($_SESSION[$this->context]["Menu"]);
		}
		else {
			$_SESSION[$this->context]["Menu"] = serialize($this->main_menu);
		}
	
    }
    

    //set selected menu items and add submenu 'arr_menu' to last selected node
    public function update($arr_menu=array())
    {
		$this->selectActiveMenus(MainMenu::FIND_INDEX_PATHCHECK);
		
		//
		$old_selected = array();
		$this->findSelectedPath($old_selected, $this->main_menu);
		
		$old_selected = array_reverse($old_selected);
		
		if (!isset($old_selected[0])) {
		  //nothing selected from previous page? throw 
		  return;
		}

		$old_selected = $old_selected[0];
		
		
		$enable_add = true;
		
		foreach($arr_menu as $key=>$subitem) {

			  if (strpos("javascript:",$subitem->getHref())===0) {

			  }
			  else {
				//update relative path of submenu items passed from page
				$subitem->setHref( dirname($_SERVER['PHP_SELF']). "/".$subitem->getHref());
			  }
			  
			  
		}
		
		foreach($arr_menu as $key=>$subitem) {
			//item is current ?
			if (strcmp($old_selected->getHref(), $subitem->getHref())===0) {
			  $enable_add = false;
			  break;
			}
		}
		
		$old_selected->clearChildNodes();
		
		if ($enable_add) {
		  
		  foreach($arr_menu as $key=>$subitem) {
			$old_selected->addMenuItem($subitem);
		  }
		 
		  
		}
		
		$this->selectActiveMenus(MainMenu::FIND_INDEX_LOOSE);
		
		$last_selected = $this->selected_path[0];
		
		
		//append page to submenu
		$match = $this->matchItem(MainMenu::FIND_INDEX_LOOSE, $last_selected);

		if (!$match) {

			  $page = SitePage::getInstance();
			  
			  if ($page->getAccessibleTitle()) {
				  $last_selected->clearChildNodes();
				  
				  $action_title = $page->getAccessibleTitle();
				  $action_item = new MenuItem($action_title , $_SERVER['REQUEST_URI']);
				  $last_selected->addMenuItem($action_item);
				  $this->selectActiveMenus(MainMenu::FIND_INDEX_LOOSE);
			  } 
			  
			
		}
		
	
		$this->selected_path = array_reverse($this->selected_path);

		$_SESSION[$this->context]["Menu"] = serialize($this->main_menu);

	// 	debug("LocationPath::pathUpdate: Storing MenuElements: ");
	// 	$this->dumpMenu();
	// 	
	// 	debug("LocationPath::pathUpdate: Current pathElements: ");
	// 	$this->dumpPath();
	
    }
    public function dumpPath()
    {
		debug("DumpPath Start");
		foreach($this->selected_path as $index=>$item) {
			debug("[$index]=>".$item->getTitle()." | ".$item->getHref());
		}
		debug("DumpPath End");
    }
    public function dumpMenu()
    {
		$menu = $this->main_menu;//unserialize($_SESSION[$this->context]["Menu"]);
		
		debug("---DumpMenu Start");
		foreach($menu as $index=>$item) {
			debug("[$index]=>".$item->getTitle()." | ".$item->getHref());
			$this->dumpMenuSub($item, 0);
		}
		debug("---DumpMenu End");
    }
    public function dumpMenuSub($item, $level)
    {
		$level++;
		
		$submenu = $item->getSubmenu();
		
		foreach($submenu as $index=>$subitem) {
		
			$str = "[$index|$level]=>".$subitem->getTitle()." | ".$subitem->getHref();
			
			$pad = str_pad( $str , ($level*2), "-");
			debug($pad);
			
			$this->dumpMenuSub($subitem, $level);
		}
    }



}

?>