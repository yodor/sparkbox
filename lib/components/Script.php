<?php
include_once("components/Script.php");

class Script extends Component
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

}

?>