<?php
include_once("components/Component.php");

class Meta extends Component
{
    public function __construct() {
        parent::__construct();
        $this->setTagName("meta");
        $this->setClosingTagRequired(false);
        $this->setComponentClass("");
    }

    public function setContent(string $content) : void
    {
        $this->setAttribute("content", $content);
    }

    public function getContent() : string
    {
        return $this->getAttribute("content");
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if (isset($this->attributes["name"])) {
            $value = $this->attributes["name"];
            unset($this->attributes["name"]);
            $this->attributes = ["name" => $value] + $this->attributes;
        }
    }

    public function setProperty(string $value) : void
    {
        $this->setAttribute("property", $value);
    }

    public function getProperty() : string
    {
        return $this->getAttribute("property");
    }
}