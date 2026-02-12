<?php
include_once("components/Container.php");
include_once("components/BeanFormEditor.php");

include_once("class/pages/AdminPage.php");

abstract class PageTemplate extends Container implements IRequestProcessor
{
    protected SparkAdminPage $page;

    protected ?Component $view = null;

    protected ?RequestParameterCondition $request_condition = null;

    public function __construct()
    {
        parent::__construct();

        $this->initPage();

        $this->initPageActions();
    }

    abstract protected function initPage(): void;

    abstract protected function initPageActions(): void;

    abstract public function initView(): ?Component;

    public function processInput() : void
    {

    }

    public function isProcessed() : bool
    {
        return false;
    }

    public function startRender(): void
    {
        if (!$this->view) $this->initView();

        $this->processInput();

        $this->page->startRender();

        parent::startRender();
    }

    public function finishRender(): void
    {
        parent::finishRender();

        $this->page->finishRender();
    }

    public function setRequestCondition(RequestParameterCondition $request_condition) : void
    {
        $this->request_condition = $request_condition;
        foreach ($this->request_condition->getParameterNames() as $idx=>$name) {
            $this->page->addParameterName($name);
        }
    }

    public function getRequestCondition() : RequestParameterCondition
    {
        return $this->request_condition;
    }

    public function getPage() : SparkAdminPage
    {
        return $this->page;
    }

}