<?php
interface IDataBean
{

    public function getCount();

    public function getFields();
    
    public function startIterator($filter="", $fields="");
    
    public function fetchNext(&$row, $iterator=false);

    public function deleteID($id);
    
    public function getByID($id);
    
    public function getByRef($refkey, $refid);

    public function deleteRef($refkey, $refval);
    
    public function haveField($field_name);
	
    public function getPrKey();
    
    public function startFieldIterator($filter_field, $filter_value);
}
?>