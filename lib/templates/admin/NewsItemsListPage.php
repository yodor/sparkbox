<?php
include_once("templates/admin/BeanListPage.php");
include_once("components/renderers/cells/ImageCellRenderer.php");
include_once("components/renderers/cells/DateCellRenderer.php");

class NewsItemsListPage extends BeanListPage
{

    public function __construct()
    {
        parent::__construct();

        //TODO
        //$this->page->checkAccess($access_role);

        $this->page->setName("News Items");

        $this->setBean(new NewsItemsBean());
        $this->setListFields(array("item_photo"=>"Photo", "item_title"=>"Title", "item_date"=>"Date"));

    }

    public function initView()
    {
        parent::initView();
        $ticr = new ImageCellRenderer();
        $ticr->setBean($this->bean);

        $this->view->getColumn("item_photo")->setCellRenderer($ticr);
        $this->view->getColumn("item_date")->setCellRenderer(new DateCellRenderer());
        return $this->view;
    }
}