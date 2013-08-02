<?php
include_once("lib/utils/SelectQuery.php");
include_once("lib/beans/NestedSetBean.php");

class ProductSelector  {


  protected $catID = -1;
  protected $brandID = -1;
  protected $searchFilter = "";
  protected $selectQuery = NULL;

  //pcb = ProductCategoriesBean
  public function __construct($catID, $brandID, NestedSetBean $pcb, $search_filter="")
  {

	  $this->catID = $catID;
	  $this->brandID = $brandID;
	  $this->searchFilter = $search_filter;
	  $this->pcb = $pcb;
	  $this->selectQuery = new SelectQuery();

	  $this->constructQuery();


  }
  public function getSelectQuery()
  {
	  return $this->selectQuery;
  }
  protected function constructQuery()
  {


	   if ($this->catID>0 && strcmp_isset("filter", "category")) {
		$this->selectQuery->where = "  sellable_products.catID=child.catID AND node.catID='{$this->catID}' ";
		$this->selectQuery->from = "  sellable_products ";
		$this->selectQuery->fields = "  sellable_products.* ";

		//intersect
		$this->selectQuery = $this->pcb->childContentsWith($this->selectQuery);
	  }
	  else if ($this->brandID>0 && strcmp_isset("filter", "brand")) {

		$this->selectQuery->from = "   sellable_products, brands ";
		$this->selectQuery->fields = "  sellable_products.*, brands.* ";

		$this->selectQuery->group_by = " sellable_products.prodID " ;
		
		$this->selectQuery->where = "   brands.brandID='{$this->brandID}' AND sellable_products.brandID=brands.brandID ";
		if ($this->catID>0) {
		  $this->selectQuery->where.=" AND sellable_products.catID=child.catID and node.catID='{$this->catID}' ";

		  $this->selectQuery = $this->pcb->childContentsWith($this->selectQuery);
		}
		else {
		  
		  $this->selectQuery = $this->pcb->parentContentsWith($this->selectQuery);
		}

	  }
	  else if (strcmp_isset("filter","search")) {
		$this->selectQuery->from = " sellable_products, brands, genders ";
		$this->selectQuery->fields = "  sellable_products.*, brands.*, genders.* ";

		$this->selectQuery->where = " sellable_products.brandID=brands.brandID AND sellable_products.gnID=genders.gnID  ";

		$this->selectQuery->group_by = " sellable_products.prodID " ;
		
		if ($this->catID>0) {
		  $this->selectQuery->where.=" AND sellable_products.catID=child.catID and node.catID='{$this->catID}' ";
		  $this->selectQuery->where.= " AND ".$this->searchFilter;
		  
		  $this->selectQuery = $this->pcb->childContentsWith($this->selectQuery);
		}
		else {
		  $this->selectQuery->where.=" AND sellable_products.catID=node.catID ";
		  $this->selectQuery->where.= " AND ".$this->searchFilter;

		  $this->selectQuery = $this->pcb->parentContentsWith($this->selectQuery);
		}

	  }
	  else {
		//all or special
		$this->selectQuery->where = " sellable_products.catID=child.catID AND node.parentID=0 ";
		$this->selectQuery->from = " sellable_products ";
		$this->selectQuery->fields = " sellable_products.* ";

		$this->selectQuery = $this->pcb->childContentsWith($this->selectQuery);
	  }
  }

}

?>