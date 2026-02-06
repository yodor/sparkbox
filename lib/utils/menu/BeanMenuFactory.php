<?php
include_once("utils/menu/MenuItem.php");
include_once("utils/menu/MenuItemList.php");

class BeanMenuFactory
{

    /**
     * @var MenuItemList|null
     */
    protected ?MenuItemList $main_menu = null;

    /**
     * @var NestedSetBean|null
     */
    protected ?NestedSetBean $bean;

    /**
     * During construct() is used to set the MenuItem title
     * Default "menu_title"
     * @var string
     */
    protected string $label_key = "menu_title";

    /**
     * During construct() is used to set the data parameter of target_url
     * Default "menuID". Should correspond to the NestedSetBean primary key
     * @var string
     */
    protected string $value_key = "menuID";

    /**
     * During construct() is used to set the href of the MenuItem using DataParameter ($value_key)
     * @var URL|null
     */
    protected ?URL $target_url = null;

    protected ?SQLSelect $select = null;

    public function __construct(NestedSetBean $bean, string $label_key = "menu_title", string $value_key = "menuID")
    {

        if (!$bean->haveColumn($value_key)) throw new Exception("Value key '$value_key' not found in bean columns");
        if (!$bean->haveColumn($label_key)) throw new Exception("Label key '$label_key' not found in bean columns");

        $this->bean = $bean;

        $this->label_key = $label_key;
        $this->value_key = $value_key;

        $this->select = clone $this->bean->select();

        $this->select->fields()->set($this->value_key, $this->label_key);

        $fieldsAddition = array("link", "seo_title", "seo_description");
        foreach($fieldsAddition as $key=>$value) {
            if ($this->bean->haveColumn($value)) {
                $this->select->fields()->set($value);
            }
        }

        $this->select->order_by = " lft ASC ";
    }

    /**
     * Use this href to set the MenuItem $href during construct()
     * The url is parametrized with query parameter = $this->bean->key()
     * Overrides link column from menu if present
     * @param string $build_href
     */
    public function setTargetURL(string $build_href) : void
    {
        $this->target_url = new URL($build_href);
        $this->target_url->add(new DataParameter($this->bean->key()));
        $this->select->fields()->unset("link");
    }

    public function menu() : MenuItemList
    {
        $menu = new MenuItemList();
        $this->fill($menu);
        $menu->setName(get_class($this->bean));
        return $menu;
    }

    /**
     * @param MenuItemList $parent
     * @return void
     * @throws Exception
     */
    protected function fill(MenuItemList $parent) : void
    {

        $parentID = 0;
        if ($parent instanceof MenuItem) {
            $parentID = $parent->getID();
        }

        $this->select->where()->clear();
        $this->select->where()->add("parentID", $parentID);

        $qry = new SQLQuery();

        if ($qry->exec($this->select) < 1) return;

        while ($result = $qry->nextResult()) {
            $parent->append($this->createItem($result));
        }

        $qry->free();

        $iterator = $parent->iterator();
        while ($item = $iterator->next()) {
            if ($item instanceof MenuItem) {
                $this->fill($item);
            }
        }

    }

    protected function createItem(RawResult $result) : MenuItem
    {
        $menuID = (int)$result->get($this->value_key);
        trbean($menuID, $this->label_key, $result->arrayRef(), $this->bean->getTableName());

        $href = "";

        if ($this->target_url instanceof URL) {
            $this->target_url->setData($result->arrayRef());
            $href = $this->target_url->toString();
        }
        else if ($result->isSet("link")) {
            $href = $result->get("link");


            // - url is internal root
            if (str_starts_with($href, "/")) {
                $href = Spark::Get(Config::LOCAL) . $href;
            }
            else {
                Debug::ErrorLog("Using external URL");
            }
        }

        $item = new MenuItem($result->get($this->label_key), $href);
        $item->setID($menuID);
        $item->enableTranslation(FALSE);

        if ($result->isSet("seo_title")) {
            $item->setSeoTitle($result->get("seo_title"));
        }
        if ($result->isSet("seo_description")) {
            $item->setSeoDescription($result->get("seo_description"));
        }
        return $item;
    }
}