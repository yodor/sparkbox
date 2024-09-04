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
    protected ?DBTableBean $bean = null;

    /**
     * @var array
     */
    protected array $fields = array();

    /**
     * @var SQLQuery|null
     */
    protected ?SQLQuery $query = null;

    /**
     * @var ActionCollection
     */
    protected ?ActionCollection $view_item_actions = null;

    protected KeywordSearch $keyword_search;

    public function __construct()
    {
        parent::__construct();
        $this->keyword_search = new KeywordSearch();
    }

    public function getIterator() : SQLQuery
    {
        return $this->query;
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

    public function removeListFields(string ...$names)
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                unset($this->fields[$name]);
            }
        }
    }
    /**
     * Set the list view column names and labels using the list_fields array
     * Array keys are used as column names and values as column labels
     * Try to set bean query fields using the names
     * @param array $list_fields
     */
    public function setListFields(array $list_fields): void
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
            if ($this->bean->haveColumn($name)) {
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
        $url = SparkPage::Instance()->getURL();
        $url->setScriptName("add.php");
        $action_add = new Action(SparkAdminPage::ACTION_ADD, $url->url());
        $action_add->setTooltipText("Add new element to this collection");
        $this->getPage()->getActions()->append($action_add);
    }

    /**
     * Initialize the item actions collection
     * Append the default "Edit" and "Delete" actions
     * If the bean instance is null the delete action is not added
     *
     * @param ActionCollection $act
     */
    protected function initViewActions(ActionCollection $act)
    {

        $act->append(new Action(SparkAdminPage::ACTION_EDIT, "add.php", array(new DataParameter("editID", $this->view->getIterator()->key()))));
        $act->append(Action::PipeSeparator());

        if ($this->bean instanceof DBTableBean) {
            $h_delete = new DeleteItemResponder($this->bean);
            $act->append($h_delete->createAction());
        }

        if ($this->bean instanceof OrderedDataBean) {

            $h_repos = new ChangePositionResponder($this->bean);

            $bkey = $this->bean->key();
            $repos_param = array(new DataParameter("item_id", $bkey));
            //
            $act->append(Action::RowSeparator());

            $action_prev = new Action("Previous", "?cmd=reposition&type=previous", $repos_param);
            $action_prev->setTooltipText("Move element one position backward");
            $act->append($action_prev);

            $act->append(Action::PipeSeparator());

            $action_next = new Action("Next", "?cmd=reposition&type=next", $repos_param);
            $action_next->setTooltipText("Move element one position forward");
            $act->append($action_next);
            //
            $act->append(Action::RowSeparator());
            //

            $action_first = new Action("First", "?cmd=reposition&type=first", $repos_param);
            $action_first->setTooltipText("Move element to first position");
            $act->append($action_first);

            $act->append(Action::PipeSeparator());

            $action_last = new Action("Last", "?cmd=reposition&type=last", $repos_param);
            $action_last->setTooltipText("Move element to last position");
            $act->append($action_last);

            $act->append(Action::RowSeparator());

            $action_choose = new Action("Set position", "?cmd=reposition&type=fixed", $repos_param);
            $action_choose->setTooltipText("Input element position");
            $act->append($action_choose);

            //

        }


    }

    /**
     * Get the item view actions collection
     * @return ActionCollection
     */
    public function viewItemActions() : ActionCollection
    {
        return $this->view_item_actions;
    }


    /**
     * Initialize the contents of the container
     * Set the $this->view instance to TableView
     * Set the $this->view_item_actions instance as ActionsTableCellRenderer
     * Calls initViewActions
     * Return the initialized TableView (Used when initView is called externaly) before startRender()
     * This method is automatically called from startRender() of PageTemplate , before processInput
     * @return TableView
     * @throws Exception
     */
    public function initView()
    {
        if (!$this->query) throw new Exception("Query not set yet");

        if (count($this->keyword_search->getForm()->getFields()) > 0) {
            $this->items()->append($this->keyword_search);
        }

        $this->view = new TableView($this->query);
        $this->view->setDefaultOrder($this->query->key()." DESC");

        $this->view->addColumn(new TableColumn($this->query->key(), "ID", TableColumn::ALIGN_CENTER));

        foreach ($this->fields as $name => $label) {
            $this->view->addColumn(new TableColumn($name, $label));
        }

        $this->view->addColumn(new TableColumn("actions", "Actions"));

        $act = new ActionsCellRenderer();
        $this->view_item_actions = $act->getActions();

        $this->initViewActions($this->view_item_actions);

        if ($this->view_item_actions->count()>0) {
            $this->view->getColumn("actions")->setCellRenderer($act);
        }
        else {
            $this->view->removeColumn("actions");
        }

        $this->items()->append($this->view);

        if ($this->bean instanceof OrderedDataBean) {
            $this->view->setDefaultOrder(" position ASC ");

            if ($this->view->haveColumn("position")) {
                $this->view->removeColumn($this->query->key());
                $this->view->getColumn("position")->setAlignClass("center");
            }


        }

        return $this->view;

    }

}
