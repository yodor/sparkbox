<?php
include_once("templates/admin/BeanListPage.php");
include_once("beans/FAQSectionsBean.php");
include_once("beans/FAQItemsBean.php");

class FAQItemsListPage extends BeanListPage
{
    public function __construct()
    {
        parent::__construct();

        $sections = new FAQSectionsBean();
        $bean = new FAQItemsBean();

        $qry = $bean->query();
        $qry->select->from.= " fi LEFT JOIN faq_sections fs ON fs.fqsID = fi.fqsID ";
        $qry->select->fields()->set("fi.fID", "fs.section_name", "fi.question", "fi.answer");

        $this->setIterator($qry);
        $this->setListFields(array("section_name"=>"Section", "question"=>"Question", "answer"=>"Answer"));
        $this->setBean($bean);

    }
    protected function initPage(): void
    {
        parent::initPage();

        $menu = array(new MenuItem("Sections", "sections/list.php"));

        $this->getPage()->setPageMenu($menu);
    }
}