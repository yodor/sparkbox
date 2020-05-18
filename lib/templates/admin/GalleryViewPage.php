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
        $h_delete = new DeleteItemRequestHandler($this->bean);
        RequestController::addRequestHandler($h_delete);
        $h_repos = new ChangePositionRequestHandler($this->bean);
        RequestController::addRequestHandler($h_repos);

        $gv = new GalleryView($this->bean);

        $this->view = $gv;

        $this->append($this->view);

        $this->view_actions = $gv->getItemActions();
    }

}