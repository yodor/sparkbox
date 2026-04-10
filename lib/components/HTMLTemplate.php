<?php
include_once("components/Container.php");

class HTMLTemplate extends Container
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("");
        $this->setTagName("template");
    }
}