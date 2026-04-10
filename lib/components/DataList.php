<?php
include_once("components/Container.php");

class DataListItem extends Component
{
    protected string $value;
    protected string $label;

    public function __construct(string $value="", string $label="")
    {
        parent::__construct(false);
        $this->value = $value;
        $this->label = $label;
        $this->setTagName("option");
    }

    protected function syncAttrs(): void
    {
        parent::syncAttrs();
        $this->setAttribute("value", $this->value);
        $this->setAttribute("label", $this->label);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

}

class DataList extends Container
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setTagName("datalist");

    }

}