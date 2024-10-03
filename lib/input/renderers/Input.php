<?php
include_once("components/Container.php");

class Input extends Container
{

    public function __construct(string $type="", string $name="", string $value = "")
    {
        parent::__construct(false);
        $this->setTagName("input");
        $this->setClosingTagRequired(false);

        if ($type) {
            $this->setType($type);
        }
        if ($name) {
            $this->setName($name);
        }
        if ($value) {
            $this->setValue($value);
        }
    }

    public function setType(string $type) : void
    {
        $this->setAttribute("type", $type);
    }

    public function getType() : string
    {
        return $this->getAttribute("type");
    }

    public function setValue(string $value) : void
    {
        $this->setAttribute("value", $value);
    }

    public function getValue() : string
    {
        return $this->getAttribute("value");
    }

}