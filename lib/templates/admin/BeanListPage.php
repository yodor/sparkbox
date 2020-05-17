<?php
include_once("templates/admin/AdminPageTemplate.php");
include_once("components/renderers/IActionsCollection.php");

class BeanListPage extends AdminPageTemplate
{

    /**
     * @var DBTableBean
     */
    protected $bean;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var TableView
     */
    protected $view;

    /**
     * @var SQLQuery
     */
    protected $query;

    /**
     * @var IActionsCollection
     */
    protected $view_actions;

    public function __construct()
    {
        parent::__construct();
    }

    public function setListFields(array $list_fields)
    {
        $this->fields = $list_fields;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function setIterator(SQLQuery $qry)
    {
        $this->query = $qry;
    }

    public function getView()
    {
        return $this->view;
    }

    protected function initPageActions()
    {
        $action_add = new Action("", "add.php");
        $action_add->setAttribute("action", "add");
        $action_add->setAttribute("title", "Add Item");
        $this->page->addAction($action_add);
    }

    protected function initViewActions(IActionsCollection $act)
    {
        if ($this->bean instanceof DBTableBean) {
            $h_delete = new DeleteItemRequestHandler($this->bean);
            RequestController::addRequestHandler($h_delete);
        }
        $act->addAction(new Action("Edit", "add.php", array(new DataParameter("editID", $this->view->getIterator()->key()))));
        $act->addAction(new PipeSeparator());
        $act->addAction($h_delete->createAction());

    }

    public function viewActions()
    {
        return $this->view_actions;
    }

    public function initView()
    {

        if (!$this->query instanceof SQLQuery) {
            $qry = $this->bean->query();
            $qry->select->fields = $qry->key() . "," . implode(", ", array_keys($this->fields));
            $this->query = $qry;
        }

        $this->view = new TableView($this->query);
        $this->view->addColumn(new TableColumn($this->query->key(), "ID"));

        foreach ($this->fields as $name => $label) {
            $this->view->addColumn(new TableColumn($name, $label));
        }

        $this->view->addColumn(new TableColumn("actions", "Actions"));

        $this->view_actions = new ActionsTableCellRenderer();
        $this->initViewActions($this->view_actions);

        $this->view->getColumn("actions")->setCellRenderer($this->view_actions);

        $this->append($this->view);
    }

}