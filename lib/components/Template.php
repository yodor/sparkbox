<?php
include_once("components/Container.php");

class Template extends Container
{

    protected string $id = "";

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("");
        $this->setTagName("template");
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if ($this->id) {
            $this->setAttribute("id", $this->id);
        }
    }

    public function setID(string $id): void
    {
        $this->id = $id;
    }

    public function getID(): string
    {
        return $this->id;
    }

}