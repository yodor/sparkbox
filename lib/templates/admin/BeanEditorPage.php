<?php
include_once("templates/admin/AdminPageTemplate.php");

class BeanEditorPage extends AdminPageTemplate
{
    protected $bean;
    protected $form;

    public function __construct()
    {
        parent::__construct();
    }

    protected function initPageActions()
    {

    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
        if (!$this->page->getName()) {
            $this->page->setName("Item Data: ".get_class($bean));
        }
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function setForm(InputForm $form)
    {
        $this->form = $form;
    }

    public function getForm(): ?InputForm
    {
        return $this->form;
    }

    public function processInput()
    {
        $this->view->processInput();
    }

    public function initView()
    {
        $view = new BeanFormEditor($this->bean, $this->form);

        $this->view = $view;

        $this->append($view);
    }

    public function getEditor(): BeanFormEditor
    {
        return $this->view;
    }
}