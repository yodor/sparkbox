<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/items/NestedSetItemRenderer.php");
include_once("lib/beans/NestedSetBean.php");
include_once("lib/utils/SelectQuery.php");
include_once("lib/utils/IQueryFilter.php");
include_once("lib/utils/ISelectSource.php");

class NestedSetTreeView extends Component implements IHeadRenderer
{

    public $open_all = true;
    public $list_label = "";
    
    protected $data_source = NULL;
    protected $select_qry = NULL;

    protected $item_renderer = NULL;

    //related
    protected $related_source = NULL;
    protected $related_filters = array();
    protected $filter_values = array();

    protected $selection_path = array();

    protected $selected_nodeID = -1;
    
    protected $filter_select = NULL;
    
    public function __construct()
    {
	parent::__construct();

	$this->setClassName("TreeView");

    }
    public function renderStyle()
    {
	echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/TreeView.css' type='text/css' >";
	echo "\n";
    }
    public function renderScript()
    {

	echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/TreeView.js'></script>";
	echo "\n";

    }
    
    public function getSelectionPath()
    {
	return $this->selection_path;
    }


    //used in the main list to fetch the matched products with category
    public function getRelationSelect()
    {
    
	$related_table =  $this->related_source->getTableName();
	$related_prkey = $this->related_source->getPrKey();

	$source_prkey = $this->data_source->getPrKey();

	$sqry = new SelectQuery();
	$sqry->fields = " $related_table.* ";

	$sqry->from = "  $related_table ";
	$sqry->where = " $related_table.$source_prkey = child.$source_prkey ";
	$sqry->group_by = " $related_table.$related_prkey ";

	if ($this->filter_select) {

	    $sqry = $sqry->combineWith($this->filter_select);

	}

	//apply other plain filters
// 	foreach ($this->related_filters as $filter_name=>$filter_key) {
// 
// 	    if (strcmp_isset("filter", $filter_name)) {
// 		//check filter 
// 		if ($filter_key instanceof IQueryFilter) {
// 		    $filter_query = $filter_key->getQueryFilter();
// 		    if ($filter_query->where) {
// 		      $sqry->where.= " AND ".$filter_query->where;
// 		    }
// 		}
// 		else {
// 		    $filter_value = $this->filter_values[$filter_key];
// 		    $sqry->where.= " AND $related_table.$filter_key = '$filter_value' ";
// 		}
// 		
// 	    }
// 	}
	

	$sqry = $this->data_source->childNodesWith($sqry, $this->selected_nodeID);

	return $sqry;

    }

    
    //filter=category&catID=15 =>filter_name = brand; filter_key=brandID
    public function addRelatedFilter($filter_name, $filter_key)
    {
	if ($filter_key instanceof IQueryFilter) {
	
	}
	else if (strcmp($filter_key, $this->data_source->getPrKey())==0) {
	    throw new Exception("Data source primary key can not be used as filter value");
	}
	
	$this->related_filters[$filter_name] = $filter_key;
    }
    
    public function processFilters()
    {

		$source_prkey = $this->data_source->getPrKey();

		if ( isset($_GET[$source_prkey]) ) {

		  $this->selected_nodeID = (int)$_GET[$source_prkey];
		  $this->selection_path = $this->data_source->constructPath($this->selected_nodeID);

		}

		$have_filter = false;

		//parse plain filters 
		foreach ($this->related_filters as $filter_name=>$filter_key) {
			if ($filter_key instanceof IQueryFilter) {

			}
			else if (isset($_GET[$filter_key])) {
			  $this->filter_values[$filter_key] = DBDriver::get()->escapeString($_GET[$filter_key]);
			}
		}


		$text_action = $this->getItemRenderer()->getTextAction();

		if ( isset($_GET["filter"]) && isset($this->related_filters[$_GET["filter"]])) {

			$requested_filter = $_GET["filter"];
			
			$have_filter = true;
			$this->open_all = true;

			$filter_key = $this->related_filters[$requested_filter];

			if ($filter_key instanceof IQueryFilter) {

				$this->filter_select = $filter_key->getQueryFilter();
			
			}
			else if ($this->related_source instanceof DBTableBean) {

				$related_table =  $this->related_source->getTableName();
				$related_prkey = $this->related_source->getPrKey();

				if (isset($this->filter_values[$filter_key])) {
				
					$filter_value = $this->filter_values[$filter_key];
					
					$sel = new SelectQuery();
					$sel->fields = "";
					$sel->from = "";
					$sel->where = " $related_table.$filter_key='$filter_value' ";
					
					$this->filter_select = $sel;
					
				}
				else {
				  debug("NestedSetTreeView::processFilters: filter requested without corresponding key value");
				}
			}
			
			if ($this->filter_select) {

			  $this->select_qry = $this->select_qry->combineWith($this->filter_select);
			
			}

			$text_action->addParameter(new ActionParameter($source_prkey, $source_prkey));

		}
		else {

		  $text_action->prependRequestParams(false);
		  $text_action->addParameter(new ActionParameter("filter", "self", true));
		  $text_action->addParameter(new ActionParameter($source_prkey, $source_prkey));
		  
		}

		return $have_filter;
    }
    
    
    public function getSource()
    {
	return $this->data_source;
    }
    public function getSelectQuery()
    {
	return $this->select_qry;
    }
    public function setSelectQuery(SelectQuery $qry)
    {
	$this->select_qry = $qry;
    }
    public function setItemRenderer(NestedSetItemRenderer $renderer)
    {
	$this->item_renderer = $renderer;
    }
    public function getItemRenderer()
    {
	return $this->item_renderer;
    }

    public function setSource(NestedSetBean $bean)
    {

	$sqry = $bean->listTreeSelect();

	//take first text or char field
	$storage_types = $bean->getStorageTypes();

	$this->list_label = $bean->getPrKey();

	foreach($storage_types as $field_name=>$storage_type) {
	  if (strpos($storage_type,"char")!==false || strpos($storage_type,"text")!==false) {
	    $this->list_label = $field_name;
	    break;
	  }
	}
	
	$this->setAttribute("source", get_class($bean));

	$this->setSelectQuery($sqry);
	
	$this->data_source = $bean;

	
    }
    //related count calculated on $count_field default counting on the prkey of the $related_source
    public function setRelatedSource(DBTableBean $related_source, $count_field="", $related_fields=null)
    {

        if (!is_array($related_fields)) {
            $related_fields = array($this->list_label);
        }
	$sqry = $this->data_source->listTreeRelatedSelect($related_source, $count_field, $related_fields);
	
	$this->setSelectQuery($sqry);

	$this->related_source = $related_source;
	
    }
    public function renderImpl()
    {

	if (! ($this->data_source instanceof NestedSetBean)) throw new Exception("No suitable data_source assigned");
	if (! ($this->item_renderer instanceof NestedSetItemRenderer)) throw new Exception("No suitable item_renderer assigned"); 

	$request_source = false;
	$related_prkey = false;

	//keep the url clean from the related source prkey for navigation
	if ($this->related_source) {
	    $related_prkey = $this->related_source->getPrKey();
	    if (isset($_GET[$related_prkey])) {
		$request_source = $_GET[$related_prkey];
		unset($_GET[$related_prkey]);
	    }
	}

	
	
	$db = DBDriver::get();

// 	echo "<div style='display:none;' class='debug'>";
// 	echo "QS:".microtime(true);
	
	$sql = $this->select_qry->getSQL();

// 	echo "<div>$sql</div>";

	$res = $db->query($sql);

	$path=array();

	$source_key = $this->data_source->getPrKey();
	
	$open_tags = 0;
	
	
// 	echo "QF:".microtime(true);
// 	echo "</div>";
	
	echo "<ul class='NodeChilds'>";
	$ctr= 0;
	
	
	while ($row = $db->fetch($res)) {
            $ctr++;
            
	  $lft = $row["lft"];
	  $rgt = $row["rgt"];
	  $nodeID = $row[$source_key];
	  
	  $render_mode = NestedSetItemRenderer::BRANCH_CLOSED;
	  if ($this->open_all) {
	      $render_mode = NestedSetItemRenderer::BRANCH_OPENED;
	  }
	  if ($rgt == $lft+1) {
	      $render_mode = NestedSetItemRenderer::BRANCH_LEAF;
	  }

	  trbean($nodeID, $this->list_label, $row, $this->data_source);

	  while (count($path)>0 && $lft > $path[count($path)-1] ) {
	      array_pop($path);

	      if ($open_tags>=1) {
		echo "</ul>";
		echo "</li>";
		$open_tags-=2;
	      }

	  }
	  $path[] = $rgt;

	  $path_len = count($path);

	  echo "<li class='NodeOuter'>";
	  $open_tags++;
	  
	    $selected = ($nodeID == $this->selected_nodeID) ? true : false;

	    $item = clone $this->item_renderer;
	    $item->setID($nodeID);
	    $item->setDataRow($row);
	    $item->setBranchType($render_mode);
	    $item->setSelected($selected);
	    $item_label = $row[$this->list_label];
	    if (isset($row["related_count"])) {
	      $item_label.=" (".$row["related_count"].")";
	    }
	    $item->setLabel($item_label);

	    
	    $item->render();

	    echo "<ul class='NodeChilds'>";
	    $open_tags++;
	}
	
// 	for ($a=0;$a<$open_tags;$a+=2) {
// 	  echo "</ul>";
// 	  echo "</li>";
// 	}
	echo "</ul>";
	
	//restore url
	if ($request_source) {
	    $_GET[$related_prkey] = $request_source;	
	}

	
    }
    
    public function finishRender()
    {
	parent::finishRender();
	?>
	<script type='text/javascript'>
	addLoadEvent(
	  function() {
	      var tree_view = new TreeView();
	      tree_view.attachWith("<?php echo $this->getName(); ?>");
	  }
	);
	</script>
	<?php
    }

}
