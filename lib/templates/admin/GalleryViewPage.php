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

        $this->setListFields(array($this->bean->key()=>"ID", "position"=>"Position", "caption"=>"Caption", "date_upload"=>"Date Upload"));

        if ($this->request_condition instanceof BeanKeyCondition) {
            $this->bean->select()->where()->addURLParameter($this->request_condition->getURLParameter());
            $this->query->select->where()->addURLParameter($this->request_condition->getURLParameter());
        }

        $h_delete = new DeleteItemResponder($this->bean);

        $h_repos = new ChangePositionResponder($this->bean);


        $gv = new GalleryView($this->bean, $this->query);

        $this->view = $gv;

        if (count($this->keyword_search->getForm()->getFields()) > 0) {
            $this->append($this->keyword_search);
        }

        $this->append($this->view);

        $this->view_item_actions = $gv->getItemActions();

        return $this->view;
    }

}