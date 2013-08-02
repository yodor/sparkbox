<?php
include_once("lib/iterators/SQLIterator.php");

class BeanResultIterator implements SQLIterator {


	private $bean;
	protected $fields;
	public $debug = false;
	public function __construct(DBTableBean $bean, $fields=" * ", $debug=false)
	{
		
		$this->bean = $bean;
		$this->fields = $fields;
		$this->debug = $debug;
	}
	public function setFields($fields_str)
	{
		$this->fields = $fields_str;
	}
	public function getBean()
	{
	  return $this->bean;
	}
	public function getSelectQuery()
	{
		return $this->bean->getSelectQuery();
	}
	public function startQuery(SelectQuery $filter = NULL)
	{

	  return $this->bean->startSelectIterator($filter, $this->debug);

	}
	public function haveMoreResults(&$row)
	{
		return $this->bean->fetchNext($row);
	}
	public function getPrKey() {
		return $this->bean->getPrKey();
	}

}
?>