<?php
include_once("templates/admin/BeanListPage.php");

include_once("beans/AdminUsersBean.php");

include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");

class NewsItemsListPage extends BeanListPage
{

    public function __construct()
    {
        parent::__construct();

        //TODO
        //$this->page->checkAccess($access_role);

        $this->page->setName("News Items");

        $this->setBean(new NewsItemsBean());
        $this->setListFields(array("photo"=>"Photo", "item_title"=>"Title", "item_date"=>"Date"));

    }

    public function initView()
    {
        parent::initView();
        $ticr = new TableImageCellRenderer();
        $ticr->setBean($this->bean);

        $this->view->getColumn("photo")->setCellRenderer($ticr);
    }
}