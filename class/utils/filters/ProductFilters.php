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

?>