<?php
include_once("forms/InputForm.php");
include_once("beans/MenuItemsBean.php");

class MenuItemInputForm extends InputForm
{

    public function __construct(NestedSetBean $source)
    {

        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "menu_title", "Menu Title", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "link", "Link", 1);
        $field->content_after = "<a class='ActionRenderer DynamicPageChooser' href='" . ADMIN_LOCAL . "content/pages/list.php?chooser=1'>" . tr("Choose Dynamic Page") . "</a>";
        $this->addInput($field);

        $field = new DataInput("parentID", "Parent Menu", 1);

        $rend = new NestedSelectField($field);

        // $source = new MenuItemsBean();
        $rend->na_label = "--- TOP ---";
        $rend->na_value = "-1";

        $rend->setItemIterator(new SQLQuery($source->listTreeSelect(), "menuID", $source->getTableName()));
        $rend->getItemRenderer()->setValueKey("menuID");
        $rend->getItemRenderer()->setLabelKey("menu_title");

        $this->addInput($field);

    }

    public function loadBeanData($editID, DBTableBean $bean)
    {
        parent::loadBeanData($editID, $bean);
        $this->load();
    }

    public function loadPostData(array $arr): void
    {
        parent::loadPostData($arr);
        $this->load();

    }

    public function load()
    {

        if (isset($_GET["page_class"]) && isset($_GET["page_id"])) {

            $page_class = $_GET["page_class"];
            $page_id = (int)$_GET["page_id"];

            $link_url = LOCAL . "content/index.php?page_class=$page_class&page_id=$page_id";

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
