<?php
include_once("templates/content/BeanList.php");
include_once("components/GalleryView.php");

class BeanGallery extends BeanList
{
    public function __construct()
    {
        parent::__construct();

    }

    public function setBean(DBTableBean $bean): void
    {
        parent::setBean($bean);
        $this->setListFields(array($this->bean->key() => "ID", "position" => "Position", "caption" => "Caption", "date_upload" => "Date Upload"));

    }

    public function initialize(): void
    {

        if (Template::Condition() instanceof BeanKeyCondition) {
            $this->bean->select()->where()->addURLParameter(Template::Condition()->getURLParameter());
            $this->query->select->where()->addURLParameter(Template::Condition()->getURLParameter());
        }

        $h_delete = new DeleteItemResponder($this->bean);

        $h_repos = new ChangePositionResponder($this->bean);

        $this->cmp = new GalleryView($this->bean, $this->query);

    }

    public function galleryView(): GalleryView
    {
        if ($this->cmp instanceof GalleryView) return $this->cmp;
        throw new Exception("Incorrect component class - expected GalleryView");
    }

    protected function getContentTitle(): string
    {
        return "Gallery";
    }
}