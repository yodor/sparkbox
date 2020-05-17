<?php
include_once("templates/admin/BeanListPage.php");
include_once("components/GalleryView.php");

class GalleryViewPage extends BeanListPage
{
    public function __construct()
    {
        parent::__construct();

        //$fields = array($this->bean->key(), "position", "caption", "date_upload");

        $this->page->setName("Photo Gallery"); // . ": " . $rc->getData("description"));
    }

    protected function initPageActions()
    {
        $action_add = new Action("", "add.php");
        $action_add->setAttribute("action", "add");
        $action_add->setAttribute("title", "Add Item");
        $this->page->addAction($action_add);
    }

    public function initView()
    {
        $h_delete = new DeleteItemRequestHandler($this->bean);
        RequestController::addRequestHandler($h_delete);
        $h_repos = new ChangePositionRequestHandler($this->bean);
        RequestController::addRequestHandler($h_repos);

        $gv = new GalleryView($this->bean);

        $this->view = $gv;

        $this->append($this->view);

        $this->view_actions = $gv->viewActions();
    }

}