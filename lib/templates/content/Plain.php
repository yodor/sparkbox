<?php

include_once("templates/TemplateContent.php");

class Plain extends TemplateContent
{

    public function __construct()
    {
        parent::__construct();
        $this->cmp = new Container();
    }

    public function initialize(): void
    {

    }

    public function component(): Component
    {
        return $this->cmp;
    }

}