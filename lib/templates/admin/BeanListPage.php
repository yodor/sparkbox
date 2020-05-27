<?php
include_once("templates/admin/AdminPageTemplate.php");
include_once("utils/ActionCollection.php");
include_once("components/KeywordSearch.php");
include_once("responders/DeleteItemResponder.php");
include_once("responders/ChangePositionResponder.php");

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

    protected $keyword_search;

    public function __construct()
    {
        parent::__construct();
        $this->keyword_search = new KeywordSearch();

    }

    /**
     * Called just before rendering is about to start to process the user input
     */
    public function processInput()
    {
        parent::processInput();

        //keyword is not enabled?
        if (count($this->keyword_search->getForm()->getFields()) < 1) return;

        $this->keyword_search->processInput();

        if ($this->keyword_search->isProcessed()) {
            $this->keyword_search->getForm()->prepareClauseCollection("OR")->copyTo($this->query->select->where());
        }


    }

    /**
     * Return the KeywordSearch component used
     * @return KeywordSearch
     */
    public function getSearch(): KeywordSearch
    {
        return $this->keyword_search;
    }

    /**
     * Set the list view column names and labels using the list_fields array
     * Array keys are used as column names and values as column labels
     * Try to set bean query fields using the names
     * @param array $list_fields
     */
    public function setListFields(array $list_fields)
    {
        $this->fields = $list_fields;

        //query is already set
        if ($this->query instanceof SQLQuery) return;

        //no bean is set yet
        if (!$this->bean) return;

        //try set the bean query
        $this->setBeanQuery();
    }

    protected function setBeanQuery()
    {

        $qry = $this->bean->query();
        $sel = $qry->select;
        $sel->fields()->set($this->bean->key());

        foreach($this->fields as $name=>$label) {
            if ($this->bean->haveField($name)) {
                $sel->fields()->set($name);
            }
        }

        $this->query = $qry;
    }

    /**
     * Set the bean instance to '$bean'
     * If the view iterator is not set and listFields are set - create iterator using setBeanQuery
     *
     * @param DBTableBean $bean
     */
    public function setBean(DBTableBean $bean)
    {
        parent::setBean($bean);

        if (!$this->page->getName()) {
            $this->page->setName("List: ".get_class($bean));
        }

        //query is already setup nothing to do
        if ($this->query instanceof SQLQuery) return;

        //no list fields set yet. query fields will be set when setListFields is called
        if (!is_array($this->fields) || count($this->fields)<1) return;

        $this->setBeanQuery();
    }

    /**
     * Set the iterator that will be used
     * @param SQLQuery $qry
     */
    public function setIterator(SQLQuery $qry)
    {
        $this->query = $qry;
    }

    public function getView()
    {
        return $this->view;
    }

    /**
     * Fill the required actions to the local page instance
     */
    protected function initPageActions()
    {
        $action_add = new Action(SparkAdminPage::ACTION_ADD, "add.php");
        $action_add->setAttribute("title", "Add Item");
        $this->getPage()->getActions()->append($action_add);
    }

    /**
     * Append the default "Edit" and "Delete" actions
     * If the bean instance is null the delete action is not added
     * @param ActionCollection $act
     */
    protected function initViewActions(ActionCollection $act)
    {

        $act->append(new Action(SparkAdminPage::ACTION_EDIT, "add.php", array(new DataParameter("editID", $this->view->getIterator()->key()))));
        $act->append(new PipeSeparator());

        if ($this->bean instanceof DBTableBean) {
            $h_delete = new DeleteItemResponder($this->bean);
            $act->append($h_delete->createAction());
        }

        if ($this->bean instanceof OrderedDataBean) {

            $h_repos = new ChangePositionResponder($this->bean);

            $bkey = $this->bean->key();
            $repos_param = array(new DataParameter("item_id", $bkey));
            //
            $act->append(new RowSeparator());

            $act->append(new Action("Previous", "?cmd=reposition&type=previous", $repos_param));
            $act->append(new PipeSeparator());
            $act->append(new Action("Next", "?cmd=reposition&type=next", $repos_param));
            //
            $act->append(new RowSeparator());
            //
            $act->append(new Action("First", "?cmd=reposition&type=first", $repos_param));
            $act->append(new PipeSeparator());
            $act->append(new Action("Last", "?cmd=reposition&type=last", $repos_param));

            $act->append(new RowSeparator());

            $act->append(new Action("Choose position", "?cmd=reposition&type=fixed", $repos_param));

            //

        }


    }

    /**
     * Get the item view actions collection
     * @return ActionCollection
     */
    public function viewItemActions()
    {
        return $this->view_actions;
    }

    /**
     * Initialize the contents of the container
     * Set the main view instance to TableView
     * Set the view_actions instance as the actions of the ActionsTableCellRenderer
     * Calls initViewActions
     *
     * This method is automatically called from startRender() of PageTemplate , before processInput
     *
     */
    public function initView()
    {
        if (!$this->query) throw new Exception("Query not set yet");

        if (count($this->keyword_search->getForm()->getFields()) > 0) {
            $this->append($this->keyword_search);
        }

        $this->view = new TableView($this->query);
        $this->view->addColumn(new TableColumn($this->query->key(), "ID", "center"));

        foreach ($this->fields as $name => $label) {
            $this->view->addColumn(new TableColumn($name, $label));
        }

        $this->view->addColumn(new TableColumn("actions", "Actions"));

        $act = new ActionsCellRenderer();
        $this->view_actions = $act->getActions();

        $this->initViewActions($this->view_actions);

        if ($this->view_actions->count()>0) {
            $this->view->getColumn("actions")->setCellRenderer($act);
        }
        else {
            $this->view->removeColumn("actions");
        }

        $this->append($this->view);

    }

}