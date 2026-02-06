<?php
include_once("templates/PageTemplate.php");

abstract class AdminPageTemplate extends PageTemplate
{
    /**
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

    public function __construct()
    {
        parent::__construct();

    }

    protected function initPage(): void
    {
        $this->page = new AdminPage();
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;

    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }
}
