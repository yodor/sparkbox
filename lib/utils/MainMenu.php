<?php
include_once("lib/utils/MenuItem.php");


class MainMenu
{

	protected $bean_class = NULL;
	protected $prefix_root = "";

	protected $selected_item = NULL;

	protected $name = "";
	
	
	const FIND_INDEX_NORMAL = 1;
	const FIND_INDEX_LOOSE = 2;
	const FIND_INDEX_LOOSE_REVERSE = 3;
	const FIND_INDEX_LEVENSHTEIN = 4;
	const FIND_INDEX_PATHCHECK = 5;
	
	protected $bean = NULL;
	
	protected $menu_map = array();
	
	protected $main_menu = array();
	
	protected $selected_path = array();
	
	
	public $uri_match_variable = "REQUEST_URI";
	
	public function __construct()
	{
	    $this->bean_class = NULL;
	    $this->prefix_root = "";
	}
	
	public function setName($name)
	{
	    $this->name = $name;
	}
	
	public function getName()
	{
	    return $this->name;
	}
	
	public function setMenuItems(array $arr_menu)
	{
	    $this->main_menu = $arr_menu;

	}

	public function getMenuItems()
	{
	    return $this->main_menu;
	}

	public function getSelectedItem()
	{
	    return $this->selected_item;

	}
	
	public function setSelectedItem(MenuItem $item)
	{
	    $this->selected_item = $item;
	}

	public function setMenuBeanClass($menu_items_class, $prefix_root="")
	{
	    $this->bean_class = $menu_items_class;
	    $this->bean = new $this->bean_class();
	    $this->prefix_root = $prefix_root;

	}
	public function getMenuBeanClass()
	{
	    return $this->bean_class;
	}
	public function constructMenuItems($parentID=0, MenuItem $parent_item = NULL, $key="menuID",$title="menu_title")
	{
	    $arr_menu = array();

	    $total_items = 0;
	    
	    $sql = $this->bean->createIteratorSQL("WHERE parentID='$parentID' ORDER BY lft");
	    
	    $iterator = $this->bean->createIterator($sql, $total_items);

	    if ($total_items<1) return;


	    while ($this->bean->fetchNext($row, $iterator)) {
	    
		    $menuID = (int)$row[$key];

		    trbean($menuID, $title, $row, $this->bean);

		    $link = "";
		    if (strlen($this->prefix_root)>0) {
		      $link.=$this->prefix_root;
		    }

		    if(isset($row["link"])) {
			$menu_link = $row["link"];
			if (strpos($menu_link,"/")===0) {
			  if (strcmp(SITE_ROOT,"/")!==0) {
				  $menu_link = SITE_ROOT.$menu_link;
			  }
			}
			$link.=$menu_link;
		    }
		    else {

		    }
		    
		    $item = new MenuItem($row[$title], $link);
		    $item->enableTranslation(false);
// 		    $item->setMenuID($menuID);
		    if ($parentID==0) {
			$arr_menu[]=$item;
		    }
		    if ($parent_item) {
			$parent_item->addMenuItem($item);
		    }
		    $this->constructMenuItems($menuID, $item, $key, $title);
	    }

	    if ($parentID==0) {
		$this->main_menu = $arr_menu;
	    }
	    
	    $db  = $this->bean->getDB();
	    
	    if (is_resource($iterator))$db->free($iterator);
	}


	public function flattenMenu(&$arr, $current_menu)
	{
	    if (!$current_menu || count($current_menu)<1) return;

	    for ($a=0;$a<count($current_menu);$a++){
		    $curr = $current_menu[$a];
		    $arr[] = $curr;
		    $this->flattenMenu($arr, $curr->getSubmenu());
	    }
	}

	public function findMenuIndex($find_mode=MainMenu::FIND_INDEX_NORMAL, $find_arr=false)
	{
	    $this->selindex=-1;

	    $match_min = PHP_INT_MAX;
	    $closest = NULL;

	    if (!$find_arr) $find_arr = $this->main_menu;

	    $position = -1;
	    
	    for ($a=0; $a<count($find_arr); $a++) {
		$curr = $find_arr[$a];

		$match =  $this->matchItem($find_mode, $curr);

		if ( $match === true )
		{
		    $this->selected_item=$curr;
		    $position = $a;
		    break;
		}
		else if ($match !== false) {
		    if ($match < $match_min) {
			    $match_min = $match;
			    $closest=$curr;
			    $position = $a;
		    }
		    else if ($match == $match_min) {
			    $match1 =  $this->matchItem(MainMenu::FIND_INDEX_LOOSE_REVERSE, $closest);
			    $match2 =  $this->matchItem(MainMenu::FIND_INDEX_LOOSE_REVERSE, $curr);
			    $closest = ($match1 ) ? $closest : $curr;
			    $position = $a;
		    }

		}
	    }

	    if ($match_min < PHP_INT_MAX && $closest) {
		$this->selected_item = $closest;
	    }
	    if ($find_mode===MainMenu::FIND_INDEX_PATHCHECK) {
		if ($position>-1) {
		  $this->selected_item = $find_arr[$position];
		}
	    }
	    return $position;

	}
	public function matchItem($find_mode, MenuItem $item)
	{
	    $href = $item->getHref();

	    $request = $_SERVER[$this->uri_match_variable];

	    $match = (strcmp( $request , $href ) == 0);

// 	    debug("matchItem  Mode: $find_mode | Request: $request Mathing With MenuItem: $href");
	    
	    if ($find_mode === MainMenu::FIND_INDEX_LOOSE) {
		$match = ( strpos(  $request, $href ) !== false );
	    }
	    else if ($find_mode === MainMenu::FIND_INDEX_LOOSE_REVERSE) {
		$match = ( strpos( $href,  $request ) !== false );
	    }
	    else if ($find_mode === MainMenu::FIND_INDEX_LEVENSHTEIN) {
		$match = @levenshtein($request, $href);

	    }
	    else if ($find_mode === MainMenu::FIND_INDEX_PATHCHECK) {
		$breq = dirname($request);
		if (endsWith($request, "/")===true) {
		  $breq = $request;
		}
		
		$hreq = dirname($href);
		if (strpos($breq, $hreq)===false) return false;
		
		$match = @levenshtein($request, $href);
		
	    }
// 	    debug("matchItem Result: ".(int)$match);
	    return $match;
	}

	public function setUnselectedAll()
	{
	    foreach($this->arr as $index=>$sub)
	    {	
		$sub->setSelected(false);
	    }
	}
	
	public function selectActiveMenus($find_mode = MainMenu::FIND_INDEX_LEVENSHTEIN)
	{
	    $this->selected_path = array();
	    
	    $arr = array();
	    $this->flattenMenu($arr, $this->main_menu);
	    
	    debug("MenuItem::selectActiveMenus: Flattened menu length: ".count($arr));
	    
	    $this->findMenuIndex($find_mode, $arr);

	    foreach($arr as $index=>$sub)
	    {	
		$sub->setSelected(false);
	    }
	
	    if ($this->selected_item) {

		$this->selected_item->setSelected(true);

		$current = $this->selected_item;

		$this->selected_path[] = $current;

		while ($current->getParent()) {

		    $parent = $current->getParent();
		    $parent->setSelected(true);

		    $current = $parent;

		    $this->selected_path[] = $current;
		}

	    }

	}
	public function getSelectedPath()
	{
	    return $this->selected_path;
	}
	
	public function findSelectedPath(&$path, $search_items=false)
	{
	    if (!$search_items) $search_items = $this->main_menu;
	    
	    foreach($search_items as $key=>$item) {
	      if ($item->isSelected()) {
		$path[] = $item;
		
		$this->findSelectedPath($path, $item->getSubmenu());
	      }
	    }
	}
}
?>