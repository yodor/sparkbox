<?php

include_once("templates/TemplateContent.php");

class Plain extends TemplateContent
{

    public function __construct()
    {
        parent::__construct();

    }

    public function initialize(): void
    {
        $this->cmp = new Container();
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return "PlainContent";
    }

}