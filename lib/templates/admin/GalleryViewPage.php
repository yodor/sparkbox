<?php
include_once("templates/admin/BeanListPage.php");
include_once("components/GalleryView.php");

class GalleryViewPage extends BeanListPage
{
    public function __construct()
    {
        parent::__construct();

        $this->page->setName("Photo Gallery"); // . ": " . $rc->getData("description"));
    }

    public function initView()
    {
        if ($this->request_condition instanceof BeanKeyCondition) {
            $this->bean->select()->where = $this->request_condition->getURLParameter()->text(TRUE);
        }

        $h_delete = new DeleteItemResponder($this->bean);

        $h_repos = new ChangePositionResponder($this->bean);

        $gv = new GalleryView($this->bean);

        $this->view = $gv;

        $this->append($this->view);

        $this->view_actions = $gv->getItemActions();
    }

}