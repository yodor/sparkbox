<?php
include_once("templates/admin/BeanListPage.php");
include_once("components/GalleryView.php");

class GalleryViewPage extends BeanListPage
{
    public function __construct()
    {
        parent::__construct();

        $this->page->setName("Photo Gallery");
    }

    public function initView()
    {
        if ($this->request_condition instanceof BeanKeyCondition) {
            $this->bean->select()->where()->addURLParameter($this->request_condition->getURLParameter());
        }

        $h_delete = new DeleteItemResponder($this->bean);

        $h_repos = new ChangePositionResponder($this->bean);

        $gv = new GalleryView($this->bean);

        $this->view = $gv;

        $this->append($this->view);

        $this->view_item_actions = $gv->getItemActions();
    }

}