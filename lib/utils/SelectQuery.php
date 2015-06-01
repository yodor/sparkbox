<?php
include_once("lib/utils/SQLQuery.php");

class SelectQuery extends SQLQuery {

  public function __construct()
  {

	  $this->type="SELECT ";
  }
  public function getSQL($where_only=false, $add_calc=true)
  {
	  $sql = "";

	  if ($where_only) {
	  }
	  else {
		  if ($add_calc) {
			$sql.=$this->type." SQL_CALC_FOUND_ROWS {$this->fields} FROM {$this->from} ";
		  }
		  else {
			$sql.=$this->type."  {$this->fields} FROM {$this->from} ";
		  }
	  }

	  
	  
	  if (strlen(trim($this->where))>0) {
		 $sql.= " WHERE ".$this->where." ";
	  }

	  if (strlen(trim($this->group_by))>0) {
		$sql.=" GROUP BY ".$this->group_by." ";
	  }
	  if (strlen(trim($this->having))>0) {
		  $sql.=" HAVING ".$this->having;
	  }
	  if (strlen(trim($this->order_by))>0) {
		$sql.=" ORDER BY ".$this->order_by." ";
	  }
	  if (strlen(trim($this->limit))>0) {
		$sql.=" LIMIT ".$this->limit." ";
	  }

	  return $sql;
  }

  public function combineWith(SelectQuery $other)
  {
	  $csql = clone $this;


	  if (strlen(trim($other->fields))>0) {

		  if (strlen($csql->fields)>0) {
					  $csql->fields.=" , ";
		  }
		  $csql->fields.=$other->fields;

	  }

	  if (strlen(trim($other->from))>0) {
		  $check = strtolower(trim($other->from));
		  if (strpos($check,"join")===0 || strpos($check,"left join")===0 || strpos($check,"right join")===0 || strpos($check,"inner join")===0) {
			if (strlen(trim($csql->from))) {
			  $csql->from.= $other->from;
			}
			else {
			  $csql = $other->from;
			}
		  }
		  else {
			if (strlen(trim($csql->from))) {
			  $csql->from.=" , ".$other->from;
			}
			else {
			  $csql->from = $other->from;
			}
		  }
	  }
	  

	  if (strlen(trim($csql->where))>0) {
		  if (strlen(trim($other->where))>0) {
			$csql->where =$csql->where." AND ".$other->where;
		  }
	  }
	  else {
		  $csql->where = $other->where;
	  }

	  if (strlen(trim($csql->group_by))>0) {
		  if (strlen(trim($other->group_by))>0) {
			$csql->group_by.=" , ".$other->group_by;
		  }
	  }
	  else if (strlen(trim($other->group_by))>0) {
		  $csql->group_by.=$other->group_by;
	  }

if (strlen(trim($csql->having))>0) {
  if (strlen(trim($other->having))>0) {
	  $csql->having.=" AND ".$other->having;
  }
}
else if (strlen(trim($other->having))>0) {
	$csql->having.=$other->having;
}

	  if (strlen(trim($csql->order_by))>0) {
		  if (strlen(trim($other->order_by))>0) {
			$csql->order_by.=" , ".$other->order_by;
		  }
	  }
	  else if (strlen(trim($other->order_by))>0) {
			$csql->order_by.=$other->order_by;
	  }

	  if (strlen(trim($other->limit))>0) {
			$csql->limit.=" ".$other->limit;
	  }
	  return $csql;
  }
  public function combineSection($section, $where)
  {
// 		$where = " parent.parentID='$parentID' ";
		if (strlen(trim($this->$section))>0) {
		  $this->$section.=" AND $where";
		}
		else {
		  $this->$section = $where;
		}
  }
}

?>