<?php
include_once("components/Component.php");

class Script extends Container
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("");
        $this->setTagName("SCRIPT");
        $this->setType("text/javascript");
    }

    public function setType(string $type) : void
    {
        $this->setAttribute("type", $type);
    }

    public function getType() : string
    {
        return $this->getAttribute("type");
    }

    public function setSrc(string $src) : void
    {
        $this->setAttribute("src", $src);
    }
    public function getSrc() : string
    {
        return $this->getAttribute("src");
    }

}

?>