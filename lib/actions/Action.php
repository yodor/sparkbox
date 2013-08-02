<?php
include_once("lib/utils/Paginator.php");

class ActionParameter
{
  public $param_name;
  public $field_name;
  public $is_value_param;

  //When is_value is true field_name is passed directly as value to the link construction. When is false the field_name is looked into the rendering row and return its value insted
  public function __construct($param_name, $field_name, $is_value_param=false)
  {
	  $this->param_name = $param_name;
	  $this->field_name = $field_name;
	  $this->is_value_param = $is_value_param;
  }
}

class Action {

  protected $title="";
  protected $parameters=NULL;
  protected $href="";
  protected $check_code="return true;";
  protected $attributes=NULL;

  /**
  @title = "Choose Client",
  @href = "clients.php?cmd=delete_item"
  @parameters = array("item_id","prodID")
  */
  public function __construct($title, $href, array $parameters, $check_code="return true;")
  {
      $this->title = $title;
      $this->href = $href;

      $this->parameters = $parameters;
      $this->check_code = $check_code;
      $this->attributes = array();
      
      $this->prepend_request_params = true;

  }
  public function prependRequestParams($mode)
  {
    $this->prepend_request_params = ($mode) ? true : false;
  }
  
  public function setHref($href)
  {
      $this->href = $href;
  }
  public function setAttribute($name, $value)
  {
	  $this->attributes[$name]=$value;
	  return $this;
  }
  public function getAttribute($name)
  {
	  return $this->attributes[$name];
  }
  public function getAttributes()
  {
	  return $this->attributes;
  }
  public function addParameter(ActionParameter $param)
  {
	  $this->parameters[] = $param;

  }
  public function prepareAction(&$row)
  {

  }
  public function getParameterCount()
  {
      return count($this->parameters);
  }
  public function isEmptyAction()
  {
      return ($this->getParameterCount()<1 && strlen($this->getHrefClean())<1);
  }
  public function copyAction(Action $action)
  {
	  $this->title = $action->getTitle();
	  $this->check_code = $action->getCheckCode();
	  $this->parameters = $action->getParameters();
	  $this->href = $action->getHrefClean();
	  $this->attributes = $action->getAttributes();

  }
  public function getHrefClean()
  {
	  return $this->href;
  }
  public function getHref(&$row)
  {

	  if (stripos($this->href, "javascript:")!==false) {

		  $href = $this->href;

		  foreach ($this->parameters as $pos=>$act_param)
		  {
			if (isset($row[$act_param->field_name])) {

			  $href = str_replace("%".$act_param->field_name."%", $row[$act_param->field_name], $href);
			}
		  }
		  return $href;
	  }

	  $href = array();
	  $params = array();
	  
	  if ($this->prepend_request_params) {
	    $params = $_GET;
	  }

	  $script_name = $this->href;
	  $script_params = "";
	  if (strpos($script_name, "?")!==false) {
		list($script_name, $script_params) = explode("?", $script_name);
	  }

	  $static_pairs = explode("&", $script_params);
	  foreach($static_pairs as $pos=>$pair) {
		  $param_name=$pair;
		  $param_value="";
		  if (strpos($pair, "=")!==false) {
			  list($param_name, $param_value) = explode("=", $pair);
		  }
		  if (strlen($param_name)>0) {
			  $params[$param_name]=$param_value;
		  }
	  }

	  foreach ($this->parameters as $pos=>$act_param)
	  {
		if ($act_param->is_value_param===TRUE) {
			$params[$act_param->param_name] = $act_param->field_name;
		}
		else if (isset($row[$act_param->field_name])) {
		  $params[$act_param->param_name] = $row[$act_param->field_name];
		}
	  }


	  if (strlen($script_name)>0) {
		Paginator::clearPageFilter($params);
	  }

	  return $script_name.queryString($params);

  }
	public function setTitle($title) {
			$this->title = $title;
	}
  public function getTitle()
  {
      return $this->title;
  }
  public function getCheckCode()
  {
	  return $this->check_code;
  }
  public function setCheckCode($check_code)
  {
		$this->check_code = $check_code;
  }
  public function getParameters()
  {
	  return $this->parameters;
  }

}
class PipeSeparatorAction extends Action
{
  public function __construct(){}
}
class RowSeparatorAction extends Action
{
  public function __construct(){}
}
class EmptyAction extends Action
{
  public function __construct(){}
}
?>