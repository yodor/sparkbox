<?php
include_once("templates/PageTemplate.php");

abstract class AdminPageTemplate extends PageTemplate
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function initPage()
    {
        $this->page = new AdminPage();
    }

    public function getPage() : AdminPageLib
    {
        return $this->page;
    }



}