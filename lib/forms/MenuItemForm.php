<?php
include_once("forms/InputForm.php");
include_once("beans/MenuItemsBean.php");
include_once("beans/DynamicPagesBean.php");
include_once("utils/url/DynamicPageURL.php");

class MenuItemForm extends InputForm
{

    public function __construct(NestedSetBean $source)
    {

        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT, "menu_title", "Menu Title", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "link", "Link", 1);

        //the dynamic pages list of the admin in chooser mode - its 'Choose Page' comes back here with page_id
        $back = URL::Current();
        $back->remove("page_id");
        $choose = Module::PathURL("/content/pages");
        $choose->add(new URLParameter("chooser", base64_encode($back->toString())));
        $action = new Action("Choose Dynamic Page", $choose->toString());
        $action->setContents(tr("Choose Dynamic Page"));
        $field->getRenderer()->getAddonContainer()->items()->append($action);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::NESTED_SELECT, "parentID", "Parent Menu", 1);

        $rend = $field->getRenderer();

        $rend->setDefaultOption("--- TOP ---", "0");

        $rend->setIterator(new SelectQuery($source->selectTree(array("menu_title")), "menuID", $source->table()));
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

        $this->load();
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
        //chosen in the dynamic pages list: the address of the page (the pages module)
        if (isset($_GET["page_id"])) {
            $page_id = (int)$_GET["page_id"];
            $pages = new DynamicPagesBean();
            $row = $pages->getResult($pages->key(), (string)$page_id, "item_title");
            if (is_array($row)) {
                $this->getInput("link")->setValue(DynamicPageURL::Page($page_id, (string)$row["item_title"])->toString());
            }
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