<?php
include_once("lib/utils/MainMenu.php");


class PageSessionMenu extends MainMenu {

    protected $context = "DEFAULT_CONTEXT";

    
    public function __construct($context, array $main_menu)
    {
	parent::__construct();
	
	$this->context = $context;

	$this->main_menu = array();
	
	if (isset($_SESSION[$this->context]["Menu"])) {
	    $main_menu = unserialize($_SESSION[$this->context]["Menu"]);
	}
	$this->main_menu = $main_menu;
	
	$_SESSION[$this->context]["Menu"] = serialize($this->main_menu);

// 	debug("LocationPath::CTOR: pathElements: ");
// 	$this->dumpPath();
	
    }
    
//     public function getSelectedPath()
//     {
// 	return $this->path;
//     }
    
    public function update($arr_menu=array())
    {
// 	$m = new MainMenu();
// 	$m->setMenuItems($this->main_menu);
	
// 	debug("LocationPath::pathUpdate: Current MenuElements: ");
// 	$this->dumpMenu();

	$this->selectActiveMenus(MainMenu::FIND_INDEX_PATHCHECK);
	
	
	debug("LocationPath::pathUpdate: Current path after PATHCHECK: ");
	$this->dumpPath();

	if (count($this->selected_path)>0) {
	
	    $last_selected = $this->selected_path[0];

// 	    echo $last_selected->getTitle();
	    
	    $last_submenu = $last_selected->getSubmenu();

	    $match = $this->matchItem(MainMenu::FIND_INDEX_LOOSE, $last_selected);
		debug("Current selected: '".$last_selected->getTitle()."' - FIND_INDEX_LOOSE with requestURI: ".$match);
		  
		
	    
// 	    $match = $this->matchItem(MainMenu::FIND_INDEX_PATHCHECK, $last_selected);
// 	    
	    if (!$match) {
		  $match = $this->matchItem(MainMenu::FIND_INDEX_LOOSE_REVERSE, $last_selected);
		  debug("Current selected: '".$last_selected->getTitle()."' - FIND_INDEX_LOOSE_REVERSE with requestURI: ".$match);
	    }

	    
	    if (!$match) {
// 	      debug("Appending dynamic menu item from this request");
// 	      
// 	      global $page;
// 	      $action_title = "Action Page";
// 	      if ($page && $page->getCaption()) {
// 			  $action_title = $page->getCaption();
// 	      } 
// 	      $action_item = new MenuItem($action_title , $_SERVER['REQUEST_URI']);
// 	      $action_item->setSelected(true);
// 	      
// 	      $last_selected->addMenuItem($action_item);
// 	      $last_selected = $action_item;
	    }
	    else {
		  debug("Updating href of last selected menu with last matched value");
// 		  $last_selected->setHref($this->getLastMatchValue());
		  $last_selected->setHref($_SERVER["REQUEST_URI"]);
	    }

	    debug("Clearing child nodes of current selected menu");
	    $last_selected->clearChildNodes();
	    
	    // add passed menu array as submenu items to the current selected item
	    foreach($arr_menu as $key=>$subitem) {

		    if (strpos("javascript:",$subitem->getHref())===0) {

		    }
		    else {
			  //update relative path of submenu items passed from page
		      $subitem->setHref( dirname($_SERVER['PHP_SELF']). "/".$subitem->getHref());
		      
		    }

			if (strcmp($last_selected->getHref(), $subitem->getHref())===0) {

				$last_selected->setSelected(true);
// 				$this->selected_path[] = $last_selected;
				break;

			}
			else {
				
				$last_selected->addMenuItem($subitem);
			}

	    }
	    
	
	}
	else {
	  debug("LocationPath::pathUpdate Could not select any menu for current request: ".$_SERVER["REQUEST_URI"]);
	  
	  // check any of the submenus currently added
	  $position = $this->findMenuIndex(MainMenu::FIND_INDEX_PATHCHECK);

	  debug("LocationPath::findMenuIndex Index: $position");
	  
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
//     public function getMenuItems()
//     {
// 	return $this->main_menu;//unserialize($_SESSION[$this->context]["Menu"]);
// 
//     }


}

?>