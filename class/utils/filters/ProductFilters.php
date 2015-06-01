<?php
include_once("lib/utils/SelectQuery.php");



class ColorFilter implements IQueryFilter
{
  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;

	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  if (strcmp($value, "N/A")==0 || strcmp($value, "NULL")==0) {
		$sel->where = " relation.color IS NULL ";
	  }
	  else {
		$sel->where = " relation.color='$value' ";
	  }
	}
	
	return $sel;
  }
}

class SizingFilter implements IQueryFilter
{

  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;
	
	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  if (strcmp($value, "N/A")==0 || strcmp($value, "NULL")==0) {
		$sel->where = " relation.size_value IS NULL ";
	  }
	  else {
		$sel->where = " (relation.size_values LIKE '%$value|%' OR relation.size_values LIKE '%|$value%' OR relation.size_values='$value') ";
// 		$sel->where = " $related_table.size_value='$value' ";
	  }
	}
	
	return $sel;
  }
}


class PricingFilter implements IQueryFilter
{
  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;

	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  
	  $price_range = explode("|", $value);
	  if (count($price_range)==2) {
		  $price_min = (float)$price_range[0];
		  $price_max = (float)$price_range[1];
		  
		  $sel->where = " (relation.sell_price >= $price_min AND relation.sell_price <= $price_max) ";
	  }
	  
	}
	return $sel;
  }
}

class InventoryAttributeFilter implements IQueryFilter
{

  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel_all = NULL;
	
	if ($value) {
	  
	  $sel_all = new SelectQuery();
	  $sel_all->fields = "";
	  $sel_all->from = "";
	  
	  //?inventory_attributes=Материал:Пух|Години:10
	  $all_filters = explode("|", $value);
// 	  var_dump($all_filters);
	  
	  foreach ($all_filters as $idx=>$filter) {
		if (strlen($filter)<1)continue;

		$name_value = explode(":", $filter);
		if (!is_array($name_value) || count($name_value)!=2)continue;
		
		$sel = new SelectQuery();
		$sel->fields = "";
		$sel->from = "";
	  
		//TODO: handle multiple values inside $filter_value - comma separated
		$ia_name = DBDriver::get()->escapeString($name_value[0]);
		$ia_value = DBDriver::get()->escapeString($name_value[1]);
		
		$sel->where = " (relation.inventory_attributes_all LIKE '$ia_name:$ia_value|%' OR relation.inventory_attributes_all LIKE '%|$ia_name:$ia_value|%' OR relation.inventory_attributes_all LIKE '%|$ia_name:$ia_value' OR relation.inventory_attributes_all LIKE '$ia_name:$ia_value') ";
		
		$sel_all = $sel_all->combineWith($sel);
	  }
	 
	}
// 	echo $sel_all->getSQL();
	return $sel_all;
  }
}
?>