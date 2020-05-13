<?php
include_once("components/NestedSetTreeView.php");
include_once("utils/NestedSetFilterProcessor.php");

class RelatedSourceFilterProcessor extends NestedSetFilterProcessor
{
    protected $relation_prkey = NULL;

    //combined query using all filter_selects
    protected $filter_all = NULL;

    //array of name=> IQueryFilter or 'value'
    protected $filters = array();

    //processed filters - array of name=> SelectQuery
    protected $filter_select = array();

    //processed filters - array of name=> 'value' 
    protected $filter_value = array();

    public function __construct(NestedSetBean $bean, string $relation_prkey)
    {
        parent::__construct($bean);

        $this->relation_prkey = $relation_prkey;

        $combining_filter = new SQLSelect();
        $combining_filter->fields = "";
        $combining_filter->from = "";

        $this->filter_all = $combining_filter;
    }

    public function numFilters()
    {
        return count($this->filter_value);
    }

    public function getFilterAll()
    {
        return $this->filter_all;
    }

    public function getFilterSelect($name)
    {
        if (isset($this->filter_select[$name])) {
            return $this->filter_select[$name];
        }
        else {
            return NULL;
        }
    }

    public function getFilterValue($name)
    {
        if (isset($this->filter_value[$name])) {
            return $this->filter_value[$name];
        }
        else {
            return NULL;
        }
    }

    public function appliedSelectFilters()
    {
        return $this->filter_select;
    }

    public function appliedSelectValues()
    {
        return $this->filter_value;
    }

    public function addFilter($filter_name, $filter_key)
    {
        if ($filter_key instanceof IQueryFilter) {

        }
        else if (strcmp($filter_key, $this->relation_prkey) == 0) {
            throw new Exception("Relation primary key can not be used as filter key");
        }

        $this->filters[$filter_name] = $filter_key;
    }

    protected function processGetVars(NestedSetTreeView $view)
    {
        parent::processGetVars($view);
        $this->processCombiningFilters($view);
    }

    public function processCombiningFilters(NestedSetTreeView $view)
    {
        $combining_filter = $this->filter_all;

        foreach ($this->filters as $name => $value) {

            if (!isset($_GET[$name])) continue;

            $filter_value = DBConnections::Get()->escape($_GET[$name]);
            if (!$filter_value) continue;

            $sel = new SQLSelect();
            if ($value instanceof IQueryFilter) {
                $sel = $value->filterSelect($view, $filter_value);
            }
            else {
                $sel->fields = "";
                $sel->from = "";
                $sel->where = " relation.$name='$filter_value' ";
            }
            if ($sel) {
                $combining_filter = $combining_filter->combineWith($sel);
                $this->filter_select[$name] = $sel;
                $this->filter_value[$name] = $filter_value;
            }
        }
        //echo $combining_filter->getSQL();
        $this->filter_all = $combining_filter;
    }

    protected function processTextAction(NestedSetTreeView $view)
    {
        parent::processTextAction($view);

        $text_action = $view->getItemRenderer()->getTextAction();

        foreach ($this->filter_value as $name => $value) {
            $text_action->addParameter(new URLParameter($name, $value));
        }
    }

    public function applyFiltersOn(NestedSetTreeView $view, SQLSelect &$sel, $filter_name, $skip_self = FALSE)
    {
        if (!$view) throw new Exception("Filter processing not finished");

        // $sel = $sel->combineWith($this->getFilterAll());

        foreach ($this->filter_select as $name => $qry) {
            if ($skip_self && strcmp($filter_name, $name) === 0) continue;
            $sel->combine($qry);
        }

        $nodeID = $view->getSelectedID();

        if ($nodeID > 0) {

            $sel = $this->bean->selectChildNodesWith($sel, $nodeID);
            //group by ?
        }
        return $this->getFilterValue($filter_name);
    }
}

?>
