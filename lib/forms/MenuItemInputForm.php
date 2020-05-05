<?php
include_once("lib/forms/InputForm.php");
include_once("lib/beans/MenuItemsBean.php");


class MenuItemInputForm extends InputForm
{

    public function __construct(NestedSetBean $source)
    {

        parent::__construct();

        $field = new DataInput("menu_title", "Menu Title", 1);
        $field->setRenderer(new TextField());
        $this->addInput($field);

        $field = new DataInput("link", "Link", 1);
        $field->setRenderer(new TextField());
        $field->content_after = "<a class='ActionRenderer DynamicPageChooser' href='" . ADMIN_ROOT . "content/pages/list.php?chooser=1'>" . tr("Choose Dynamic Page") . "</a>";
        $this->addInput($field);

        $field = new DataInput("parentID", "Parent Menu", 1);
        $rend = new NestedSelectField();

        // $source = new MenuItemsBean();
        $rend->na_str = "--- TOP ---";
        $rend->na_val = "0";

        $rend->setIterator($source->query());
        $rend->list_key = "menuID";
        $rend->list_label = "menu_title";

        $field->setRenderer($rend);
        $this->addInput($field);


    }

    public function loadBeanData($editID, DBTableBean $bean)
    {
        parent::loadBeanData($editID, $bean);
        $this->load();
    }

    public function loadPostData(array $arr) : void
    {
        parent::loadPostData($arr);
        $this->load();

    }

    public function load()
    {

        if (isset($_GET["page_class"]) && isset($_GET["page_id"])) {

            $page_class = $_GET["page_class"];
            $page_id = (int)$_GET["page_id"];

            $link_url = SITE_ROOT . "content/index.php?page_class=$page_class&page_id=$page_id";


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
