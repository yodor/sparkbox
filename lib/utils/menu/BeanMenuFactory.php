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
    protected ?URL $target_url;


    public function __construct(NestedSetBean $bean, string $label_key = "menu_title", string $value_key = "menuID")
    {
        $this->bean = $bean;

        $this->label_key = $label_key;
        $this->value_key = $value_key;

        if (!$this->bean->haveColumn($this->value_key)) throw new Exception("Value key '$this->value_key' not found in bean columns");
        if (!$this->bean->haveColumn($this->label_key)) throw new Exception("Label key '$this->label_key' not found in bean columns");

    }

    /**
     * Use this href to set the MenuItem $href during construct()
     * The url is parametrized with query parameter = $this->bean->key()
     * @param string $build_href
     */
    public function setTargetURL(string $build_href) : void
    {
        $this->target_url = new URL($build_href);
        $this->target_url->add(new DataParameter($this->bean->key()));
    }

    public function menu() : MenuItemList
    {
        return new MenuItemList($this->construct());
    }

    /**
     * @param int $parentID
     * @param MenuItem|NULL $parent
     * @return array
     * @throws Exception
     */
    public function construct(int $parentID = 0, MenuItem $parent = NULL) : ?array
    {

        $items_top = array();

        $qry = $this->bean->queryField("parentID", $parentID);
        $qry->select->fields()->set($this->value_key, $this->label_key);

        if ($this->bean->haveColumn("link")) {
            $qry->select->fields()->set("link");
        }

        $qry->select->order_by = " lft ASC ";

        $total_items = $qry->exec();

        //debug("Total #$total_items MenuItems with parentID=$parentID");

        if ($total_items < 1) return null;

        while ($data = $qry->next()) {

            $menuID = (int)$data[$this->value_key];

            trbean($menuID, $this->label_key, $data, $this->bean->getTableName());

            $menu_link = "";
            if (isset($data["link"])) {
                $menu_link = $data["link"];

                if (str_starts_with($menu_link, "//")) {
                    debug("Using external URL");
                }
                // - url is internal root
                else if (str_starts_with($menu_link, "/")) {
                    $menu_link = LOCAL . $menu_link;
                }
            }
            else if ($this->target_url instanceof URL) {
                $this->target_url->setData($data);
                $menu_link = $this->target_url->toString();
            }

            //debug("MenuItem ID:$menuID using menu_link: " . $menu_link);

            $item = new MenuItem($data[$this->label_key], $menu_link);
            $item->setID($menuID);

            $item->enableTranslation(FALSE);

            if ($parentID == 0) {
                $items_top[] = $item;
            }
            if ($parent) {
                $parent->append($item);
            }

            $this->construct($menuID, $item);
        }

        if ($parentID == 0) {
            return $items_top;
        }
        return null;
    }
}