<?php
include_once("lib/components/NestedSetTreeView2.php");
include_once("lib/utils/NestedSetFilterProcessor.php");

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
    
    
	public function __construct($prkey)
	{
		parent::__construct();
		$this->relation_prkey = $prkey;

		$combining_filter = new SelectQuery();
		$combining_filter->fields = "";
		$combining_filter->from = "";
		
		$this->filter_all = $combining_filter;
	}

	public function addFilter($filter_name, $filter_key)
    {
		if ($filter_key instanceof IQueryFilter) {
		
		}
		else if (strcmp($filter_key, $this->relation_prkey)==0) {
			throw new Exception("Relation primary key can not be used as filter key");
		}
		
		$this->filters[$filter_name] = $filter_key;
    }

	protected function processGetVars()
	{

		parent::processGetVars();

		$this->processCombiningFilters();
		
	}
	public function processCombiningFilters()
    {
		
		$combining_filter = $this->filter_all;

		foreach ($this->filters as $name=>$value) {
			if ( isset($_GET[$name]) ) {
				$filter_value = DBDriver::get()->escapeString($_GET[$name]);

				if ($filter_value) {
				  if ($value instanceof IQueryFilter) {
					$sel = $value->getQueryFilter($this->view, $filter_value);
					if ($sel) {
					  $combining_filter = $combining_filter->combineWith($sel);
					  
					  $this->filter_select[$name] = $sel;
					  $this->filter_value[$name] = $filter_value;
					}
				  }
				  else {
					$sel = new SelectQuery();
					$sel->fields = "";
					$sel->from = "";
					$sel->where = " relation.$name='$filter_value' ";
					$combining_filter = $combining_filter->combineWith($sel);
					
					$this->filter_select[$name] = $sel;
					$this->filter_value[$name] = $filter_value;
				  }

				}
			}
		}
// 		echo $combining_filter->getSQL();
		
		$this->filter_all = $combining_filter;

    }
	protected function processTextAction()
	{
// 		$bean = $view->getSource();
// 		$source_prkey = $bean->getPrKey();
// 		
// 		$tv_item_clicked = new Action(
// 		  "TextItemClicked", "?filter=self",
// 		  array(
// 			new ActionParameter($bean->getPrKey(), $bean->getPrKey())
// 		  )
// 		);
// 
// 		$view->getItemRenderer()->setTextAction($tv_item_clicked);
		parent::processTextAction();

		$text_action = $this->view->getItemRenderer()->getTextAction();
		
		foreach ($this->filter_value as $name=>$value) {
			$text_action->addParameter(new ActionParameter($name, $value, true));
		}
		
	}
	
	public function applyFiltersOn(&$sel, $filter_name, $skip_self=false)
	{
	  if (!$this->view) throw new Exception("Filter processing not finished");
	  
	 // $sel = $sel->combineWith($this->getFilterAll());

	  foreach ($this->filter_select as $name=>$qry)
	  {
		if ($skip_self && strcmp($filter_name, $name)===0) continue;
		$sel = $sel->combineWith($qry);
	  }
	  
	  
	  $nodeID = $this->view->getSelectedID();

	  if ($nodeID>0) {
		  $sel->where = " relation.catID = child.catID ";
		  $sel = $this->view->getSource()->childNodesWith($sel, $nodeID);
	  }
	  
	  return $this->getFilterValue($filter_name);

	}
}
?>