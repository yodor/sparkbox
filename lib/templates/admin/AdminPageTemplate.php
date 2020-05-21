<?php
include_once("templates/PageTemplate.php");

abstract class AdminPageTemplate extends PageTemplate
{
    protected $bean;

    public function __construct()
    {
        parent::__construct();

    }

    protected function initPage()
    {
        $this->page = new AdminPage();
    }

    public function getPage() : SparkAdminPage
    {
        return $this->page;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;

    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }
}