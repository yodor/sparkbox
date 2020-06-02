<?php
include_once("utils/MenuItem.php");

class MainMenu
{

    protected $selected_item = NULL;

    protected $name = "";

    /**
     * @var NestedSetBean|null
     */
    protected $bean;

    /**
     * Top parents of this MenuItem collection
     * @var array
     */
    protected $main_menu = array();

    /**
     * Indexed array holding the selection path from top parent MenuItem to selected MenuItem
     * @var array
     */
    protected $selected_path = array();

    /**
     * During construct() is used to set the href of the MenuItem using DataParameter ($value_key)
     * @var URLBuilder|null
     */
    protected $target_url;

    /**
     * During construct() is used to set the MenuItem title
     * Default "menu_title"
     * @var string
     */
    protected $label_key;

    /**
     * During construct() is used to set the data parameter of target_url
     * Default "menuID". Should correspond to the NestedSetBean primary key
     * @var string
     */
    protected $value_key;

    public function __construct()
    {
        $this->label_key = "menu_title";
        $this->value_key = "menuID";
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setMenuItems(array $arr_menu)
    {
        $this->main_menu = $arr_menu;
    }

    public function getMenuItems(): ?array
    {
        return $this->main_menu;
    }

    /**
     * Return the currently selected MenuItem
     * @return MenuItem|null
     */
    public function getSelectedItem(): ?MenuItem
    {
        return $this->selected_item;
    }


    public function getBySelectedState() :?MenuItem
    {
        $result = null;

        $items = array();
        $this->flattenMenu($items, $this->main_menu);

        for ($a = 0; $a < count($items); $a++) {

            $item = $items[$a];
            //debug("MenuItem: ".$item->getTitle()." is_selected: ".(int)$item->isSelected());
            if (!($item instanceof MenuItem)) throw new Exception("Element is not instance of MenuItem");
            if ($item->isSelected()) {
                $result = $item;
            }
            //continue until end node is selected
        }
        return $result;
    }

    /**
     * Manually set the active MenuItem
     * @param MenuItem $item
     */
    public function setSelectedItem(MenuItem $item)
    {
        $this->selected_item = $item;
        $this->unselectAll();
        $this->selected_item->setSelected(true);
        $this->constructSelectedPath();
    }

    /**
     * Return the first MenuItem after '$index' having getTitle()=$title
     * @param string $title
     */
    public function getIndexByTitle(string $title, $index = 0): int
    {
        $items = array();
        $this->flattenMenu($items, $this->main_menu);
        for ($a = $index; $a < count($items); $a++) {
            $item = $items[$a];
            if (!($item instanceof MenuItem)) throw new Exception("Element is not instance of MenuItem");
            if (strcmp($title, $item->getTitle()) == 0) return $a;
        }
        return -1;
    }

    public function getIndexByHref(string $href, int $index = 0): int
    {
        if (startsWith($href, LOCAL)) {

        }
        else {
            $href = LOCAL . $href;
        }

        $items = array();
        $this->flattenMenu($items, $this->main_menu);
        for ($a = $index; $a < count($items); $a++) {
            $item = $items[$a];
            if (!($item instanceof MenuItem)) throw new Exception("Element is not instance of MenuItem");
            if (strcmp($href, $item->getHref()) == 0) return $a;
        }
        return -1;
    }

    /**
     * Get the MenuItem with index '$index' from the flattened menu list
     * @param int $index
     * @return MenuItem
     * @throws Exception
     */
    public function get(int $index): MenuItem
    {
        $items = array();
        $this->flattenMenu($items, $this->main_menu);
        if (isset($items[$index])) return $items[$index];
        throw new Exception("Index out of range");
    }

    /**
     * Use this href to set the MenuItem $href during construct()
     * The url is parametrized with query parameter = $this->bean->key() if the bean is not set it would be parameterized
     * during the setBean call
     * @param string $build_href
     */
    public function setTargetURL(string $build_href)
    {
        $this->target_url = new URLBuilder();
        $this->target_url->buildFrom($build_href);

        if ($this->bean) {
            $this->target_url->add(new DataParameter($this->bean->key()));
        }
    }

    /**
     * Set the bean to be used during construct() call
     * @param NestedSetBean $bean
     * @throws Exception
     */
    public function setBean(NestedSetBean $bean)
    {
        $this->bean = $bean;

        if ($this->target_url) {
            $this->target_url->add(new DataParameter($this->bean->key()));
        }

        if (!$this->bean->haveColumn($this->value_key)) throw new Exception("Value key '{$this->value_key}' not found in bean columns");
        if (!$this->bean->haveColumn($this->label_key)) throw new Exception("Label key '{$this->label_key}' not found in bean columns");
    }

    /**
     *
     * @param string $key
     * @throws Exception
     */
    public function setValueKey(string $key)
    {
        $this->value_key = $key;
        if ($this->bean) {
            if (!$this->bean->haveColumn($this->value_key)) throw new Exception("Value key '{$this->value_key}' not found in bean columns");
        }
    }

    /**
     * Used as key to set the MenuItem title value
     * @param string $key
     * @throws Exception
     */
    public function setLabelKey(string $key)
    {
        $this->label_key = $key;
        if ($this->bean) {
            if (!$this->bean->haveColumn($this->label_key)) throw new Exception("Value key '{$this->label_key}' not found in bean columns");
        }
    }

    public function getMenuBeanClass(): string
    {
        if ($this->bean) return get_class($this->bean);
        return "";
    }

    /**
     * Construct MenuItems using the bean set
     * @param int $parentID
     * @param MenuItem|null $parent
     * @throws Exception
     */
    public function construct(int $parentID = 0, MenuItem $parent = NULL)
    {
        if (!$this->bean) throw new Exception("No bean assigned");

        $items_top = array();

        $qry = $this->bean->queryField("parentID", $parentID);
        $qry->select->fields()->set($this->value_key, $this->label_key);

        if ($this->bean->haveColumn("link")) {
            $qry->select->fields()->set("link");
        }

        $qry->select->order_by = " lft ASC ";

        $total_items = $qry->exec();

        //debug("Total #$total_items MenuItems with parentID=$parentID");

        if ($total_items < 1) return;

        while ($data = $qry->next()) {

            $menuID = (int)$data[$this->value_key];

            trbean($menuID, $this->label_key, $data, $this->bean->getTableName());

            $menu_link = "";
            if (isset($data["link"])) {
                $menu_link = $data["link"];

                if (strpos($menu_link, "//") === 0) {
                    debug("Using external URL");
                }
                // - url is internal root
                else if (strpos($menu_link, "/") === 0) {
                    $menu_link = LOCAL . $menu_link;
                }
            }
            else if ($this->target_url instanceof URLBuilder) {
                $this->target_url->setData($data);
                $menu_link = $this->target_url->url();
            }

            //debug("MenuItem ID:$menuID using menu_link: " . $menu_link);

            $item = new MenuItem($data[$this->label_key], $menu_link);
            //ID?
            $item->enableTranslation(FALSE);

            if ($parentID == 0) {
                $items_top[] = $item;
            }
            if ($parent) {
                $parent->addMenuItem($item);
            }

            $this->construct($menuID, $item);
        }

        if ($parentID == 0) {
            $this->main_menu = $items_top;
        }

    }

    /**
     * Return all MenuItems and their children as indexed array in '$items'
     * @param array $items the resulting array
     * @param array|null $current_menu start from this MenuItem
     * @throws Exception
     */
    public function flattenMenu(array &$items, array $current_menu = NULL)
    {
        if (!$current_menu || count($current_menu) < 1) return;

        for ($a = 0; $a < count($current_menu); $a++) {

            $item = $current_menu[$a];

            if (!($item instanceof MenuItem)) throw new Exception("Element not instance of MenuItem");

            $items[] = $item;

            $this->flattenMenu($items, $item->getSubmenu());
        }
    }

    /**
     * Set all MenuItems to selected = false
     * @param array|null $items
     * @throws Exception
     */
    public function unselectAll(array &$items = NULL)
    {
        if (!$items) {
            $items = array();
            $this->flattenMenu($items, $this->main_menu);
        }

        foreach ($items as $index => $sub) {
            $sub->setSelected(FALSE);
        }
    }

    /**
     * Select active MenuItem by matching the current page URL with the MenuItem url
     * Matches by full url, then scriptName only, then scriptPath only
     * Constructs a selection path array containing MenuItems - from top parent MenuItem to selected MenuItem
     * @throws Exception
     */
    public function selectActive()
    {
        $this->selected_path = array();

        $items = array();
        $this->flattenMenu($items, $this->main_menu);

        $this->unselectAll($items);

        $pageURL = new URLBuilder();
        $pageURL->buildFrom(currentURL());

        debug("Current URL: " . $pageURL->url());

        $match_full = function (MenuItem $item, URLBuilder $itemURL) use ($pageURL) {
            $match = (strcmp($itemURL->url(), $pageURL->url()) == 0);
            if ($match) {
                debug("Match full URL: " . $itemURL->url() . " - matches");
            }
            return $match;
        };

        $match_script = function (MenuItem $item, URLBuilder $itemURL) use ($pageURL) {
            $match = (strcmp($itemURL->getScriptName(), $pageURL->getScriptName()) == 0);
            if ($match) {
                debug("Match scriptName: " . $itemURL->getScriptName() . " - matches");
            }
            return $match;
        };

        $match_path = function (MenuItem $item, URLBuilder $itemURL) use ($pageURL) {
            $match = (strcmp($itemURL->getScriptPath(), $pageURL->getScriptPath()) == 0);
            if ($match) {
                debug("Match scriptPath: " . $itemURL->getScriptPath() . " - matches");
            }
            return $match;
        };

        $this->selected_item = $this->matchItems($items, $match_full);

        if (!$this->selected_item) {
            $this->selected_item = $this->matchItems($items, $match_script);
        }

        if (!$this->selected_item) {
            $this->selected_item = $this->matchItems($items, $match_path);
        }

        $this->constructSelectedPath();
    }

    /**
     * Execute the matching using Closure '$matcher'
     *
     * @param array $items MenuItems to match
     * @param Closure $matcher Closure function to execute during matching
     * @return MenuItem|null The matching MenuItem or NULL if none of the MenuItems '$items' match
     * @throws Exception Throws exception if element from items is not instance of MenuItem
     */
    protected function matchItems(array &$items, Closure $matcher)
    {

        $result = NULL;

        $itemURL = new URLBuilder();

        foreach ($items as $idx => $item) {
            if (!($item instanceof MenuItem)) throw new Exception("Element is not instance of MenuItem");

            $item_href = $item->getHref();

            if (endsWith($item_href, "/")) {
                $item_href .= "index.php";
            }

            $itemURL->buildFrom($item_href);

            $is_match = $matcher($item, $itemURL);

            if ($is_match) {
                $result = $item;
                break;
            }
        }

        return $result;

    }

    /**
     * Construct selection path if $this->selected_item is MenuItem
     * this function is executed from selectActive()
     * Can be used if manually setting the selected MenuItem using setSelectedMenuItem
     */
    public function constructSelectedPath()
    {
        if (!$this->selected_item) {
            debug("Selected item is null - no selection path to construct");
            return;
        }

        $this->selected_item->setSelected(TRUE);

        $current = $this->selected_item;

        $this->selected_path[] = $current;

        while ($current->getParent()) {

            $parent = $current->getParent();
            $parent->setSelected(TRUE);

            $current = $parent;

            $this->selected_path[] = $current;

        }

        $this->selected_path = array_reverse($this->selected_path);
    }

    /**
     * Returns the current selection path from top parent MenuItem to selected MenuItem
     * @return array
     */
    public function getSelectedPath(): array
    {
        return $this->selected_path;
    }

    /**
     * construct selection path using MenuItem isSelected()
     * Return array of MenuItems from top selected to inner selected
     * @param $path
     * @param $menu_items
     */
    public static function findSelectedPath(&$path, $menu_items)
    {
        // 	    if (!$search_items) $search_items = $this->main_menu;

        foreach ($menu_items as $key => $item) {
            if ($item->isSelected()) {
                $path[] = $item;
                $subitems = $item->getSubmenu();
                if (count($subitems) > 0) {
                    MainMenu::findSelectedPath($path, $subitems);
                }
            }
        }
    }
}

?>
