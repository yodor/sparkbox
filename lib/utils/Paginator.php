<?php

class PaginatorSortField
{
  
  public $value;
  public $label;
  public $extended_sort_sql;
  public $order_direction;
  
  public function __construct($value, $label, $extended_sort_sql="", $order_direction="DESC")
  {

	$this->value = $value;
	$this->label = $label;
	$this->extended_sort_sql = $extended_sort_sql;
	$this->order_direction = $order_direction;
  }
}

class Paginator
{

	protected $ipp = 10;
	protected $total_rows = 0;

	protected $show_next = true;
	protected $show_prev = true;

	protected $total_pages = 0;
	protected $page = 0;

	protected $page_list_end = 0;
	protected $page_list_start = 0;
	
	public $max_page_list = 7;


	public static $page_filter_only = false;

	
	protected $sort_fields = array();
	protected $sort_components = array();

	public $default_order_direction = "DESC";
	
	protected $order_direction = NULL;
	protected $order_field = NULL;
	
	public function getOrderDirection()
	{
	    return $this->order_direction;
	}
	public function getOrderField()
	{
	    return $this->order_field;
	}
	public function __construct()
	{

	}
	public function addSortComponent(Component $cmp)
	{
		$cmp->setParent($this);
		$this->sort_components[] = $cmp;
	}
	public function addSortField(PaginatorSortField $sort_field)
	{
	      $this->sort_fields[$sort_field->value] = $sort_field;
	}
	public function getSortFields()
	{
	      return $this->sort_fields;
	}
	public function getSortComponents()
	{
	      return $this->sort_components;
	}


	public function getSelectedSortField()
	{
	    foreach($this->sort_fields as $field_name=>$sort_field)
	    {
	      if (isset($_GET["orderby"]) && strcmp($_GET["orderby"], $sort_field->value)==0 ) {
		  return $sort_field;
	      }

	    }
	    
	    if (count($this->sort_fields)>0) {
		  $values = array_values($this->sort_fields);
		  $sf = array_shift($values);
		  return $sf;
	    }
	    else {
		  return NULL;
	    }
	}
	public function getItemsPerPage()
	{
		return $this->ipp;
	}
	public function getPageListStart()
	{
		return $this->page_list_start;
	}
	public function getPageListEnd()
	{
		return $this->page_list_end;
	}
	public function haveNextPage()
	{
		return $this->show_next;
	}
	public function havePreviousPage()
	{
		return $this->show_prev;
	}
	public function getPagesTotal()
	{
		if ($this->total_pages == (int)$this->total_pages) return $this->total_pages;
		return ((int)$this->total_pages)+1;
	}
	public function getResultsTotal()
	{
		return $this->total_rows;
	}
	public function getCurrentPage()
	{
		return $this->page;
	}
	public function calculate($total_rows, $ipp)
	{
		$this->ipp = $ipp;
		$this->total_rows = $total_rows;

if ($ipp>0) {
		$total_pages = $this->total_rows / $this->ipp;


		$qry = $_GET;

		$page=0;
		if (isset($_GET["page"]))
		{
			$page=(int)$_GET["page"];
		}

		if ($page>$total_pages)$page=$total_pages-1;
		if ($page<0)$page=0;

		echo " ";

		$max_page = $this->max_page_list;

		$cstart = $page - (int)($max_page/2);
		$cend = $page + (int)($max_page/2)+1;

		if ($cstart < 2){
			$cstart = 0;
			$cend = $max_page;
		}
		if ($cend>$total_pages){
			$cend = $total_pages;
		}
		$show_next=false;
		if ($cend < $total_pages){
			$this->show_next=true;
		}
		$show_prev=false;
		if ($cstart > 0) {
			$this->show_prev=true;

		}
}
else {
  $page=0;
  $cend = 1;
  $cstart = 1;
  $total_pages = 1;
  $this->show_prev=false;
  $this->show_next=false;
}
		$this->page = (int)$page;
		$this->page_list_end = (int)$cend;
		$this->page_list_start = (int)$cstart;
		$this->total_pages = (int)$total_pages;
	}
	public static function clearPageFilter(&$arr)
	{
		if (isset($arr["page"]))unset($arr["page"]);
		if (isset($arr["orderdir"]))unset($arr["orderdir"]);
		if (isset($arr["orderby"]))unset($arr["orderby"]);
	}

	public  function preparePageFilter($default_order="")
	{
		$filter = new SelectQuery();
		$filter->from = "";
		$filter->fields = "";

		$page = $this->page;
		$ipp = $this->ipp;
		
		
		$order_field = NULL;
		$order_direction = $this->default_order_direction;
		$orderby = $default_order;
		
		if (endsWith(trim($default_order), "ASC")) {
		  $order_direction = "ASC";
		}
		else if (endsWith(trim($default_order), "DESC")) {
		  $order_direction = "DESC";
		}
		
		if (isset($_GET["orderby"])){
		    $order_field = DBDriver::get()->escapeString($_GET["orderby"]);
		    $this->order_field = $order_field;
		}
		if (isset($_GET["orderdir"])){
		    $order_direction = DBDriver::get()->escapeString($_GET["orderdir"]);
		}

		if ($order_field) {
		    $orderby = "$order_field $order_direction";
		}
		else {
		    $order_field = $this->getSelectedSortField();
		    if ($order_field) {
			  if ($order_field->extended_sort_sql) {
				  $orderby = $order_field->extended_sort_sql;
			  }
			  else {
				  $orderby = $order_field->value." ".$order_direction;
				  $this->order_field = $order_field->value;
			  }
		    }
		}
		
		if (!self::$page_filter_only) {
		  if ($orderby) {
		    $filter->order_by = $orderby;

		  }
		}

		$this->order_direction = $order_direction;
		
		if ($ipp>0) {
		    $filter->limit = " ".($page*$ipp).", $ipp";
		}



		

		return $filter;
	}
}