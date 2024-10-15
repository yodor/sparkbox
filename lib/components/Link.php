<?php
include_once("components/Component.php");

class Link extends Component
{
    public function __construct(string $href="")
    {
        parent::__construct(false);
        $this->setTagName("LINK");
        $this->setClosingTagRequired(false);
        $this->setComponentClass("");
        $this->setRelation("stylesheet");
    }

    public function setHref(string $href) : void
    {
        $this->setAttribute("href", $href);
    }
    public function getHref() : string
    {
        return $this->getAttribute("href");
    }

    public function setRelation(string $relation) : void
    {
        $this->setAttribute("rel", $relation);
    }

    public function getRelation() : string
    {
        return $this->getAttribute("rel");
    }
}

?>