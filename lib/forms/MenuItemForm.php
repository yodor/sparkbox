<?php
include_once("forms/InputForm.php");
include_once("beans/MenuItemsBean.php");

class MenuItemForm extends InputForm
{

    public function __construct(NestedSetBean $source)
    {

        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT, "menu_title", "Menu Title", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "link", "Link", 1);

        $link = URL::Current();
        $action = new Action("Choose Dynamic Page", Spark::Get(Config::ADMIN_LOCAL) . "/content/pages/list.php?chooser=".base64_encode($link->toString()));
        $field->getRenderer()->getAddonContainer()->items()->append($action);
        $this->addInput($field);

        $field = new DataInput("parentID", "Parent Menu", 1);

        $rend = new NestedSelectField($field);

        $rend->setDefaultOption("--- TOP ---", "0");

        $rend->setIterator(new SQLQuery($source->selectTree(array("menu_title")), "menuID", $source->getTableName()));
        $rend->getItemRenderer()->setValueKey("menuID");
        $rend->getItemRenderer()->setLabelKey("menu_title");

        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXTAREA, "seo_title", "SEO Title (max 60 characters)", 0);
        $field->enableTranslator(true);
        $field->getRenderer()->input()->setAttribute("maxLength","60");
        $field->getRenderer()->input()->setAttribute("rows","3");
        $field->getRenderer()->input()->setAttribute("cols","60");
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXTAREA, "seo_description", "SEO Description (max 150 characters)", 0);
        $field->enableTranslator(true);
        $field->getRenderer()->input()->setAttribute("maxLength","150");
        $field->getRenderer()->input()->setAttribute("rows","3");
        $field->getRenderer()->input()->setAttribute("cols","60");
        $this->addInput($field);
    }

    public function loadBeanData($editID, DBTableBean $bean): array
    {
        $result = parent::loadBeanData($editID, $bean);
        $this->load();
        return $result;
    }

    public function loadPostData(array $data) : void
    {
        parent::loadPostData($data);
        $this->load();

    }

    public function load() : void
    {

        if (isset($_GET["page_id"])) {

            $page_id = (int)$_GET["page_id"];

            $link_url = "/pages/index.php?id=$page_id";

            $this->getInput("link")->setValue($link_url);
        }

    }

    public function validate(): void
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