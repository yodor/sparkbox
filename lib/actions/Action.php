<?php
include_once("utils/Paginator.php");
include_once("utils/URLParameter.php");

class ActionParameter extends URLParameter
{
    /**
     * Parameters to pass to the action getHref($row) to construct the parametrized query
     */

    protected $field;

    /**
     * @param string $param_name Parameter name
     * @param string $field_name The name of the index from the input array passed to Action.getHref(&$row)
     * @param boolean $is_value_param True: field_name is passed directly to Action.getHref(&$row) during parametrization
     *                                False: field_name is looked into the rendering row and return its value instead to Action.getHref(&$row) during parametrization
     */
    //
    public function __construct(string $param_name, string $field_name="")
    {
        if (!$field_name) {
            $field_name = $param_name;
        }
        $this->field = $field_name;

        parent::__construct($param_name, "");
    }

    public function field()
    {
        return $this->field;
    }

    public function setValue(array &$data)
    {
        if (isset($data[$this->field])) {
            $this->value = $data[$this->field];
        }
    }
}


class Action
{

    /**
     * Generic class for handling action and parametrization of its href
     */


    protected $title = "";
    protected $parameters = NULL;
    protected $href = "";
    protected $check_code = "return true;";
    protected $attributes = NULL;
    protected $clear_page_param = false;

    protected $prepend_request_params = true;
    /**
     * CTOR
     *
     * @param string $title Link title ex. "Clients List"
     * @param string $href Link href ex. "clients.php?cmd=list"
     * @param array $parameters Collection of ActionParameter objects to construct the parametrized href with
     * @param string $check_code Presentation time evaluation code. Help if this action should be rendered or not
     */
    public function __construct(string $title, string $href, array $parameters = array(), string $check_code = "return true;")
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
    public function setClearPageParam(bool $mode)
    {
        $this->clear_page_param = $mode;
    }

    /**
     * prependRequestParams
     *
     * @param boolean $mode If set to true will prepend the current request query parameters to this link href
     */
    public function prependRequestParams(bool $mode)
    {
        $this->prepend_request_params = $mode;
    }

    public function setHref(string $href)
    {
        $this->href = $href;
    }

    public function setAttribute(string $name, string $value)
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function addParameter(URLParameter $param)
    {
        $this->parameters[] = $param;
    }

    public function getParameterCount()
    {
        return count($this->parameters);
    }

    public function isEmptyAction()
    {
        return ($this->getParameterCount() < 1 && strlen($this->getHref()) < 1);
    }

    public function copyAction(Action $action)
    {
        $this->title = $action->getTitle();
        $this->check_code = $action->getCheckCode();
        $this->parameters = $action->getParameters();
        $this->href = $action->getHref();
        $this->attributes = $action->getAttributes();
    }

    /**
     * @param array $row Input array to parametrize the href with
     * @return string Return parametrized href using the input array $row
     */
    public function getHref(array &$row = NULL)
    {
        if (!is_array($row)) return $this->href;

        if (stripos($this->href, "javascript:") !== false) {

            $href = $this->href;

            foreach ($this->parameters as $pos => $act_param) {
                if ($act_param instanceof ActionParameter) {
                    $act_param->setValue($row);
                    $href = str_replace("%" . $act_param->field() . "%", $act_param->value(), $href);
                }
            }

            return $href;
        }

        $href = array();
        $params = array();

        //TODO: Check order of parameters
        //1. parameters from current URL
        if ($this->prepend_request_params) {
            foreach ($_GET as $key => $param) {
                if (!isset($params[$key])) {
                    $params[$key] = $param;
                }
            }
        }

        //2. static parameters from action href
        $script_name = $this->href;
        $script_query = "";
        if (strpos($script_name, "?") !== false) {
            list($script_name, $script_query) = explode("?", $script_name);
        }
        $static_pairs = explode("&", $script_query);
        foreach ($static_pairs as $pos => $pair) {
            $param_name = $pair;
            $param_value = "";
            if (strpos($pair, "=") !== false) {
                list($param_name, $param_value) = explode("=", $pair);
            }
            if (strlen($param_name) > 0) {
                $params[$param_name] = $param_value;
            }
        }

        //3. parameters passed in the CTOR array 'parameters' (parametrized with this data row)
        foreach ($this->parameters as $pos => $act_param) {

            if ($act_param instanceof ActionParameter) {
                $act_param->setValue($row);
            }
            //URLParameter
            $params[$act_param->name()] = $act_param->value();
        }

        //clear parameters from the Paginator
        if (strlen($script_name) > 0 || $this->clear_page_param) {
            Paginator::clearPageFilter($params);
        }

        $ret = $script_name . queryString($params);
        if (is_array($row)) {
            foreach ($row as $param_name => $value) {
                $ret = str_replace("%" . $param_name . "%", $value, $ret);
            }
        }

        if (strrpos($ret, "&") === strlen($ret) - 1) {
            $ret = substr_replace($ret, "", -1);
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
    public function __construct()
    {
        parent::__construct("", "");
    }
}

class RowSeparatorAction extends Action
{
    public function __construct()
    {
        parent::__construct("", "");
    }
}

class EmptyAction extends Action
{
    public function __construct()
    {
        parent::__construct("", "");
    }
}

?>
