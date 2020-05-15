<?php
include_once("utils/MainMenu.php");

class PageSessionMenu extends MainMenu
{

    protected $context = NULL;

    protected $dataKey = "";

    public function __construct(AuthContext $context, array $main_menu)
    {
        parent::__construct();

        $this->context = $context;

        //assign initial menu
        $this->main_menu = $main_menu;

        $this->dataKey = md5(SessionData::MENU . "|" . get_class($this) . SITE_TITLE);

        //check if there is already a menu in session and use it instead or put the inital menu to the session
        if ($context->getData()->contains($this->dataKey)) {
            $this->main_menu = unserialize($context->getData()->get($this->dataKey));
        }
        else {
            $context->getData()->set($this->dataKey, serialize($this->main_menu));
        }
    }

    //set selected menu items and add submenu 'arr_menu' to last selected node
    public function update($arr_menu = array())
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

        $enable_add = TRUE;

        foreach ($arr_menu as $key => $subitem) {

            if (strpos("javascript:", $subitem->getHref()) === 0) {
                //
            }
            else {
                //update relative path of submenu items passed from page
                $subitem->setHref(dirname($_SERVER['PHP_SELF']) . "/" . $subitem->getHref());
            }

        }

        foreach ($arr_menu as $key => $subitem) {
            //item is current ?
            if (strcmp($old_selected->getHref(), $subitem->getHref()) === 0) {
                $enable_add = FALSE;
                break;
            }
        }

        $old_selected->clearChildNodes();

        if ($enable_add) {

            foreach ($arr_menu as $key => $subitem) {
                $old_selected->addMenuItem($subitem);
            }

        }

        $this->selectActiveMenus(MainMenu::FIND_INDEX_LOOSE);

        $last_selected = $this->selected_path[0];

        //append page to submenu
        $match = $this->matchItem(MainMenu::FIND_INDEX_LOOSE, $last_selected);

        if (!$match) {

            $page = HTMLPage::Instance();

            if ($page->getAccessibleTitle()) {
                $last_selected->clearChildNodes();

                $action_title = $page->getAccessibleTitle();
                $action_item = new MenuItem($action_title, $_SERVER['REQUEST_URI']);
                $last_selected->addMenuItem($action_item);
                $this->selectActiveMenus(MainMenu::FIND_INDEX_LOOSE);
            }

        }

        $this->selected_path = array_reverse($this->selected_path);

        $this->context->getData()->set($this->dataKey, serialize($this->main_menu));

        // 	debug("LocationPath::pathUpdate: Storing MenuElements: ");
        // 	$this->dumpMenu();
        //
        // 	debug("LocationPath::pathUpdate: Current pathElements: ");
        // 	$this->dumpPath();

    }

    public function dumpPath()
    {
        debug("DumpPath Start");
        foreach ($this->selected_path as $index => $item) {
            debug("[$index]=>" . $item->getTitle() . " | " . $item->getHref());
        }
        debug("DumpPath End");
    }

    public function dumpMenu()
    {
        $menu = $this->main_menu;//unserialize($_SESSION[$this->context]["Menu"]);

        debug("---DumpMenu Start");
        foreach ($menu as $index => $item) {
            debug("[$index]=>" . $item->getTitle() . " | " . $item->getHref());
            $this->dumpMenuSub($item, 0);
        }
        debug("---DumpMenu End");
    }

    public function dumpMenuSub($item, $level)
    {
        $level++;

        $submenu = $item->getSubmenu();

        foreach ($submenu as $index => $subitem) {

            $str = "[$index|$level]=>" . $subitem->getTitle() . " | " . $subitem->getHref();

            $pad = str_pad($str, ($level * 2), "-");
            debug($pad);

            $this->dumpMenuSub($subitem, $level);
        }
    }

}

?>