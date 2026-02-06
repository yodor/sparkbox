<?php
include_once("templates/admin/AdminPageTemplate.php");
include_once("components/BeanFormEditor.php");
/**
 * If request condition is BeanKeyCondition will use it to set where filter on the view bean and add field to the transactor
 * Class BeanEditorPage
 */
class BeanEditorPage extends AdminPageTemplate
{

    /**
     * @var InputForm|null
     */
    protected ?InputForm $form = null;

    public function __construct()
    {
        //init page and page actions
        parent::__construct();
    }

    protected function initPageActions(): void
    {

    }

    public function setBean(DBTableBean $bean): void
    {
        parent::setBean($bean);
        if ($this->page->getName()) {
            $this->page->setName("Item Data: ".$this->page->getName());
        }
        else {
            $this->page->setName("Item Data: ".get_class($bean));

        }
    }

    public function setForm(InputForm $form) : void
    {
        $this->form = $form;
    }

    public function getForm(): ?InputForm
    {
        return $this->form;
    }

    public function processInput() : void
    {
        $this->view->processInput();
    }

    public function initView(): ?Component
    {
        if ($this->view) return null;

        $view = new BeanFormEditor($this->bean, $this->form);

        if ($this->request_condition instanceof BeanKeyCondition) {
            $this->bean->select()->where()->addURLParameter($this->request_condition->getURLParameter());
            $view->getTransactor()->appendURLParameter($this->request_condition->getURLParameter());
        }

        $this->view = $view;

        $this->items()->append($this->view);

        return $this->view;
    }

    public function getEditor(): BeanFormEditor
    {
        if ($this->view instanceof BeanFromEditor) return $this->view;
        throw new Exception("Incorrect view class");
    }
}
