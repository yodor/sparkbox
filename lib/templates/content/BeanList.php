<?php
include_once("templates/TemplateContent.php");
include_once("components/KeywordSearch.php");
include_once("components/TableView.php");

include_once("responders/ChangePositionResponder.php");
include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");

class BeanList extends TemplateContent
{

    /**
     * @var array
     */
    protected array $fields = array();

    /**
     * @var SelectQuery|null
     */
    protected ?SelectQuery $query = null;


    protected ?KeywordSearch $search = null;

    public function __construct()
    {
        parent::__construct();
        $this->search = new KeywordSearch();
    }

    public function getIterator(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Called just before rendering is about to start to process the user input
     * Default implementation processed the keyword search
     */
    public function processInput(): void
    {

        //keyword is not enabled?
        if (count($this->search->getForm()->getColumns()) < 1) return;

        $this->search->processInput();

        if ($this->search->isProcessed()) {

            $clauses = $this->search->getForm()->prepareClauseCollection("OR");
            //$clauses->copyTo($this->query->select->where());
            $this->query->stmt->having = $clauses->getSQL();

        }
    }

    public function isProcessed(): bool
    {
        return $this->search->isProcessed();
    }

    /**
     * Return the KeywordSearch component used
     * @return KeywordSearch
     */
    public function getSearch(): KeywordSearch
    {
        return $this->search;
    }

    public function removeListFields(string ...$names): void
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                unset($this->fields[$name]);
            }
        }
    }

    /**
     * Set the list view column names and labels using the list_fields array.
     * Array keys are used as column names and values as column labels.
     * Try to set bean query fields using the names.
     * @param array $list_fields
     */
    public function setListFields(array $list_fields): void
    {
        $this->fields = $list_fields;

        //query is already set
        if ($this->query instanceof SelectQuery) return;

        //no bean is set yet
        if (!$this->bean) return;

        //try set the bean query
        $this->setBeanQuery();
    }

    protected function setBeanQuery(): void
    {

        //copy of bean select
        $qry = $this->bean->query();
        $sel = $qry->stmt;
        $sel->set($this->bean->key());

        foreach ($this->fields as $name => $label) {
            if ($this->bean->haveColumn($name)) {
                $sel->set($name);
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
    public function setBean(DBTableBean $bean): void
    {

        parent::setBean($bean);

        //query is already setup nothing to do
        if ($this->query instanceof SelectQuery) return;

        //no list fields set yet. query fields will be set when setListFields is called
        if (count($this->fields) < 1) return;

        $this->setBeanQuery();
    }

    /**
     * Set the iterator that will be used
     * @param SelectQuery $itr
     * @throws Exception
     */
    public function setIterator(IDataIterator $itr): void
    {
        if (!$itr instanceof SelectQuery) throw new Exception("Incorrect IDataIterator - expected SelectQuery");
        $this->query = $itr;
    }

    /**
     * Fill the required actions
     */
    public function fillPageActions(ActionCollection $collection): void
    {
        $actionCreate = TemplateContent::CreateAction(SparkTemplateAdminPage::ACTION_ADD, SparkTemplateAdminPage::ACTION_ADD);
        $actionCreate->getURL()->add(new URLParameter("editID", -1));
        $actionCreate->setTooltip("Add new element to this collection");

        $collection->append($actionCreate);
    }

    public function fillPageFilters(Container $filters): void
    {
        if (count($this->search->getForm()->getColumns()) > 0) {
            $filters->items()->append($this->search);
        }
    }


    /**
     * Initialize the item actions collection
     * Append the default "Edit" and "Delete" actions
     * If the bean instance is null the delete action is not added
     * Append reordering actions if Bean is of type OrderedDataBean
     * @param ActionCollection $act
     * @return void
     * @throws Exception
     */
    protected function initItemActions(ActionCollection $act): void
    {

        $editAction = TemplateContent::CreateAction(SparkTemplateAdminPage::ACTION_EDIT, SparkTemplateAdminPage::ACTION_EDIT);
        $editAction->setTooltip(tr("Edit element"));
        $editAction->getURL()->add(new DataParameter("editID", $this->getIterator()->key()));

        $act->append($editAction);

        $act->append(Action::PipeSeparator());

        if ($this->bean instanceof DBTableBean) {
            $h_delete = new DeleteItemResponder($this->bean);
            $deleteAction = $h_delete->createAction();
            $deleteAction->setURL(Module::PathURL("", $deleteAction->getURL()));
            $act->append($deleteAction);
        }

        if ($this->bean instanceof OrderedDataBean) {

            $h_repos = new ChangePositionResponder($this->bean);

            $bkey = $this->bean->key();

            $paramCmd = new URLParameter(RequestResponder::KEY_COMMAND, ChangePositionResponder::class);
            $paramItemId = new DataParameter("item_id", $bkey);
            $paramType = new URLParameter("type", "");
            //
            $act->append(Action::RowSeparator());

            $actionPrev = TemplateContent::CreateAction("previous", tr("Previous"));
            $paramType->setValue($actionPrev->getAction());
            $actionPrev->getURL()->add($paramCmd);
            $actionPrev->getURL()->add(clone $paramType);
            $actionPrev->getURL()->add($paramItemId);
            $actionPrev->setTooltip(tr("Move element one position backward"));
            $act->append($actionPrev);

            $act->append(Action::PipeSeparator());

            $actionNext = TemplateContent::CreateAction("next", tr("Next"));
            $paramType->setValue($actionNext->getAction());
            $actionNext->getURL()->add($paramCmd);
            $actionNext->getURL()->add(clone $paramType);
            $actionNext->getURL()->add($paramItemId);
            $actionNext->setTooltip(tr("Move element one position forward"));
            $act->append($actionNext);

            $act->append(Action::RowSeparator());

            $actionFirst = TemplateContent::CreateAction("first", tr("First"));
            $paramType->setValue($actionFirst->getAction());
            $actionFirst->getURL()->add($paramCmd);
            $actionFirst->getURL()->add(clone $paramType);
            $actionFirst->getURL()->add($paramItemId);
            $actionFirst->setTooltip(tr("Move element to first position"));
            $act->append($actionFirst);

            $act->append(Action::PipeSeparator());

            $actionLast = TemplateContent::CreateAction("last", tr("Last"));
            $paramType->setValue($actionLast->getAction());
            $actionLast->getURL()->add($paramCmd);
            $actionLast->getURL()->add(clone $paramType);
            $actionLast->getURL()->add($paramItemId);
            $actionLast->setTooltip("Move element to last position");
            $act->append($actionLast);

            $act->append(Action::RowSeparator());

            $actionPos = TemplateContent::CreateAction("fixed", tr("Set Position"));
            $paramType->setValue($actionPos->getAction());
            $actionPos->getURL()->add($paramCmd);
            $actionPos->getURL()->add(clone $paramType);
            $actionPos->getURL()->add($paramItemId);
            $actionPos->setTooltip("Specify element position");
            $act->append($actionPos);

        }


    }

    /**
     * Initialize the contents of the tableview
     * @throws Exception
     */
    public function initialize(): void
    {

        if (!$this->query) throw new Exception("Query not set yet");

        //echo $this->query->select->getSQL();

        $this->cmp = new TableView($this->query);

        $this->cmp->setDefaultOrder(new OrderColumn($this->query->key() , OrderDirection::DESC));

        $this->cmp->addColumn(new TableColumn($this->query->key(), "ID", TableColumn::ALIGN_CENTER));

        foreach ($this->fields as $name => $label) {
            $this->cmp->addColumn(new TableColumn($name, $label));
        }

        $act = new ActionsCell();
        $itemActions = $act->getActions();
        $this->initItemActions($itemActions);

        if ($itemActions->count() > 0) {
            $this->cmp->addColumn(new TableColumn("actions", "Actions", TableColumn::ALIGN_CENTER));
            $this->cmp->getColumn("actions")->setCellRenderer($act);
        }

        if ($this->bean instanceof OrderedDataBean) {
            $this->cmp->setDefaultOrder(new OrderColumn("position" , OrderDirection::ASC));

            $idColumn = $this->cmp->getColumn($this->query->key());
            if ($this->cmp->haveColumn("position")) {
                if (strcmp($idColumn->getLabel(), "ID") === 0) {
                    $this->cmp->removeColumn($this->query->key());
                }
                $this->cmp->getColumn("position")->setAlignClass("center");
            }

        }

    }

    public function getItemActions() : ActionsCell
    {
        $tableView = $this->tableView();
        $actionsColumn = $tableView->getColumn("actions");
        $actionsCell = $actionsColumn->getCellRenderer();
        if ($actionsCell instanceof ActionsCell) return $actionsCell;
        throw new Exception("ActionsCell not set");

    }

    public function tableView(): TableView
    {
        if ($this->cmp instanceof TableView) return $this->cmp;
        throw new Exception("Incorrect component class - expected TableView");
    }

    public function setup(TemplateConfig $config): void
    {
        if ($config->iterator) $this->setIterator($config->iterator);
        if ($config->listFields) $this->setListFields($config->listFields);
        if ($config->searchField) $this->getSearch()->getForm()->setColumns($config->searchField);

        parent::setup($config);


    }

    protected function getContentTitle(): string
    {
        return "List";
    }
}