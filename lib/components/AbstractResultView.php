<?php
include_once("lib/pages/SitePage.php");
include_once("lib/components/Component.php");
include_once("lib/components/TableColumn.php");
include_once("lib/iterators/SQLIterator.php");
include_once("lib/utils/ValueInterleave.php");
include_once("lib/utils/Paginator.php");
include_once("lib/components/PaginatorTopComponent.php");
include_once("lib/components/PaginatorBottomComponent.php");

abstract class AbstractResultView extends Component 
{

	protected $page = false;

	public $items_per_page = 10;
	
	
	protected $itr = false;

  //   protected $columns = false;

	
	protected $default_order="";
	protected $total_rows=0;
	protected $current_row = array();
	protected $paginator;
	protected $paginator_top;
	protected $paginator_bottom;
	protected $position_index = -1;
	protected $paginators_enabled = true;
	protected $select_query = NULL;

	
	public function enablePaginators($mode)
	{
		$this->paginators_enabled = $mode;
	}
	public function getPositionIndex()
	{
		  $paginator = $this->paginator;

		  $position_index = ($paginator->getCurrentPage() * $paginator->getItemsPerPage()) + $this->position_index;

		  return $position_index;
	}
	public function __construct(SQLIterator $itr)
	{
		parent::__construct();
	
		$this->search_filter="";
// 		$this->select_query = new SelectQuery();

		$this->itr = $itr;
		$this->columns = array();
		$this->paginator = new Paginator();
		$this->paginator_top = new PaginatorTopComponent($this->paginator);
		$this->paginator_bottom = new PaginatorBottomComponent($this->paginator);
	}
	public function getTopPaginator()
	{
		return $this->paginator_top;
	}
	public function getBottomPaginator()
	{
		return $this->paginator_bottom;
	}

	public function setCaption($caption)
	{
		$this->caption = $caption;
		$this->paginator_top->setCaption($caption);
	}
	public function setDefaultOrder($default_order)
	{
		$this->default_order = $default_order;
	}

	public function startRender()
	{

		parent::startRender();
	  
		$this->total_rows=$this->itr->startQuery($this->itr->getSelectQuery());
		$this->paginator->calculate($this->total_rows, $this->items_per_page);
		      

		$pagefilter = $this->paginator->preparePageFilter( $this->default_order );

// 		echo "PageFilter SQL: ".$pagefilter->getSQL(true);

		$select = $this->itr->getSelectQuery();
// 	  	echo "Iterator SQL: ".$select->getSQL();

// if ($this->paginators_enabled) {
		$select = $select->combineWith($pagefilter);
// }
// 		echo "Final SQL: ".$select->getSQL();

		// echo $query;
		$this->total_rows=$this->itr->startQuery($select);


		
		if ($this->paginators_enabled) {
			$this->paginator_top->render();
		}
	}

	public function finishRender()
	{
		if ($this->paginators_enabled) {
			$this->paginator_bottom->render();
		}

		parent::finishRender();
	}

	

	public function getIterator()
	{
		return $this->itr;
	}
	public function getPaginator()
	{
		return $this->paginator;
	}
	public function getTotalRows()
	{
		return $this->total_rows;
	}
}
?>