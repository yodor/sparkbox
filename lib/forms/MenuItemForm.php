<?php
include_once("forms/InputForm.php");
include_once("beans/MenuItemsBean.php");

class MenuItemForm extends InputForm
{

    public function __construct(NestedSetBean $source)
    {

        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "menu_title", "Menu Title", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "link", "Link", 1);

        $link = SparkPage::Instance()->getURL();
        $action = new Action("Choose Dynamic Page", ADMIN_LOCAL . "/content/pages/list.php?chooser=".base64_encode($link->url()));
        $field->getRenderer()->getAddonContainer()->append($action);
        $this->addInput($field);

        $field = new DataInput("parentID", "Parent Menu", 1);

        $rend = new NestedSelectField($field);

        // $source = new MenuItemsBean();
        $rend->na_label = "--- TOP ---";
        $rend->na_value = "0";

        $rend->setIterator(new SQLQuery($source->selectTree(array("menu_title")), "menuID", $source->getTableName()));
        $rend->getItemRenderer()->setValueKey("menuID");
        $rend->getItemRenderer()->setLabelKey("menu_title");

        $this->addInput($field);

    }

    public function loadBeanData($editID, DBTableBean $bean)
    {
        parent::loadBeanData($editID, $bean);
        $this->load();
    }

    public function loadPostData(array $arr)
    {
        parent::loadPostData($arr);
        $this->load();

    }

    public function load()
    {

        if (isset($_GET["page_id"])) {

            $page_id = (int)$_GET["page_id"];

            $link_url = "/pages.php?id=$page_id";

            $this->getInput("link")->setValue($link_url);
        }

    }

    public function validate()
    {
        parent::validate();
        $editID = $this->getEditID();
        if ($editID > 0) {
            $parentID = $this->getInput("parentID")->getValue();
            if ($parentID == $editID) {
                $this->getInput("parentID")->setError("Can not reparent to self");
            }
        }
    }
}

?>