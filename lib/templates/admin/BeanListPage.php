<?php
include_once("templates/admin/AdminPageTemplate.php");
include_once("utils/ActionCollection.php");

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
     * @var ActionCollection
     */
    protected $view_actions;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set the view columns matching query fields
     * Array keys are used as column names and values as column labels
     * @param array $list_fields
     */
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
        $action_add = new Action(SparkAdminPage::ACTION_ADD, "add.php");
        $action_add->setAttribute("title", "Add Item");
        $this->getPage()->getActions()->append($action_add);
    }

    protected function initViewActions(ActionCollection $act)
    {
        if ($this->bean instanceof DBTableBean) {
            $h_delete = new DeleteItemRequestHandler($this->bean);
            RequestController::addRequestHandler($h_delete);
        }
        $act->append(new Action(SparkAdminPage::ACTION_EDIT, "add.php", array(new DataParameter("editID", $this->view->getIterator()->key()))));
        $act->append(new PipeSeparator());
        $act->append($h_delete->createAction());

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

        $act = new ActionsTableCellRenderer();
        $this->view_actions = $act->getActions();

        $this->initViewActions($this->view_actions);

        $this->view->getColumn("actions")->setCellRenderer($act);

        $this->append($this->view);
    }

}