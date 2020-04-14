<?php
include_once("lib/utils/Paginator.php");

class ActionParameter
{
    /**
    * Parameters to pass to the action getHref($row) to construct the parametrized query 
    */
    
    
    public $param_name;
    public $field_name;
    public $is_value_param;

    /**
    * @param string $param_name Parameter name
    * @param string $field_name The name of the index from the input array passed to Action.getHref(&$row)
    * @param boolean $is_value_param True: field_name is passed directly to Action.getHref(&$row) during parametrization
    *                                False: field_name is looked into the rendering row and return its value insted to Action.getHref(&$row) during parametrization
    */
    //
    public function __construct($param_name, $field_name, $is_value_param=false)
    {
        $this->param_name = $param_name;
        $this->field_name = $field_name;
        $this->is_value_param = $is_value_param;
    }
}


class Action {

    /**
    * Generic class for handling action and parametrization of its href
    */


    protected $title="";
    protected $parameters=NULL;
    protected $href="";
    protected $check_code="return true;";
    protected $attributes=NULL;
    protected $clear_page_param = false;
    
    /**
    * CTOR
    *
    * @param string $title Link title ex. "Clients List"
    * @param string $href  Link href ex. "clients.php?cmd=list"
    * @param array $parameters Collection of ActionParameter objects to construct the parametrized href with
    * @param string $check_code Presentation time evaluation code. Help if this action should be rendered or not 
    */
    public function __construct($title, $href, array $parameters, $check_code="return true;")
    {
        $this->title = $title;
        $this->href = $href;

        $this->parameters = $parameters;
        $this->check_code = $check_code;
        $this->attributes = array();
        
        $this->prepend_request_params = true;
        $this->clear_page_param = false;

    }
    
    //remove paginator page=? parameter from this href
    public function setClearPageParam($mode)
    {
        $this->clear_page_param = ($mode) ? true : false;
    }

    /**
    * prependRequestParams
    *
    * @param boolean $mode If set to true will prepend the current request query parameters to this link href
    */
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
  
    /**
    * @return string Return the current href property 
    */
    public function getHrefClean()
    {
        return $this->href;
    }
  
    /**
    * @param array $row Input array to parametrize the href with
    * @return string Return parametrized href using the input array $row
    */
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
        
        

        $script_name = $this->href;
        $script_params = "";
        if (strpos($script_name, "?")!==false) {
            list($script_name, $script_params) = explode("?", $script_name);
        }

        //TODO: Check order of parameters
        //1. parameters from current URL
        if ($this->prepend_request_params) {
            foreach ($_GET as $key=>$param) {
                if (!isset($params[$key])) {
                    $params[$key]=$param;
                }
            }
// 	    $params = array_merge($params, $_GET);
        }

        //2. static parameters from action href
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

        //3. parameters passed in the CTOR array 'parameters'
        foreach ($this->parameters as $pos=>$act_param)
        {
            if ($act_param->is_value_param===TRUE) {
                    $params[$act_param->param_name] = $act_param->field_name;
            }
            else if (isset($row[$act_param->field_name])) {
                $params[$act_param->param_name] = $row[$act_param->field_name];
            }
        }
        
        if (strlen($script_name)>0 || $this->clear_page_param) {
            Paginator::clearPageFilter($params);
        }

        $ret = $script_name.queryString($params);
        if (is_array($row)) {
            foreach($row as $param_name=>$value)  {
                $ret = str_replace("%".$param_name."%", $value, $ret);
            }
        }
        if (strrpos($ret,"&")===strlen($ret)-1) {
            $ret = substr_replace($ret ,"",-1);
        }
        
        return $ret;

    }
  
    public function setTitle($title) 
    {
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
