<?php
include_once("templates/admin/AdminPageTemplate.php");

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

    protected function initPageActions()
    {

    }

    public function setBean(DBTableBean $bean)
    {
        parent::setBean($bean);
        if ($this->page->getName()) {
            $this->page->setName("Item Data: ".$this->page->getName());
        }
        else {
            $this->page->setName("Item Data: ".get_class($bean));

        }
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
        if ($this->view) return;

        $view = new BeanFormEditor($this->bean, $this->form);

        if ($this->request_condition instanceof BeanKeyCondition) {
            $this->bean->select()->where()->addURLParameter($this->request_condition->getURLParameter());
            $view->getTransactor()->appendURLParameter($this->request_condition->getURLParameter());
        }

        $this->view = $view;

        $this->append($this->view);
    }

    public function getEditor(): BeanFormEditor
    {
        return $this->view;
    }
}
