<?php
include_once("utils/URLParameter.php");
include_once("utils/Paginator.php");

class URLBuilder
{

    protected $script_name = "";
    protected $script_query = "";

    protected $domain = "";
    protected $protocol = "";

    protected $parameters;

    protected $clear_page_param = FALSE;

    protected $keep_request_params = TRUE;

    public function __construct(string $href="")
    {
        $this->parameters = array();

        $this->setHref($href);
    }
    public function isEmpty()
    {
        return (count($this->parameters) < 1 && strlen($this->script_name) < 1);
    }
    //remove paginator page=? parameter from this href
    public function setClearPageParams(bool $mode)
    {
        $this->clear_page_param = $mode;
    }

    /**
     * prependRequestParams
     *
     * @param boolean $mode If set to true will prepend the current request query parameters to this link href
     */
    public function setKeepRequestParams(bool $mode)
    {
        $this->keep_request_params = $mode;
    }

    public function addParameter(URLParameter $param)
    {
        $this->parameters[$param->name()] = $param;
    }

    public function getParameter($name) : URLParameter
    {
        return $this->parameters[$name];
    }

    public function getParameterNames() : array
    {
        return array_keys($this->parameters);
    }

    public function url()
    {
        $this->process();

        $ret = $this->protocol.$this->domain.$this->script_name;

        if (stripos($ret, "javascript:") !== false) {

        }
        else if (count($this->parameters)>0) {
            $ret.="?";

            $names = array_keys($this->parameters);

            $pairs = array();
            foreach ($names as $pos=>$name) {
                $param = $this->getParameter($name);
                $pairs[] = $param->name()."=".$param->value();
            }

            $ret.=implode("&", $pairs);

        }

        return $ret;
    }

    public function getScriptName()
    {
        return $this->script_name;
    }

    public function setScriptName(string $value)
    {
        $this->script_name = $value;
    }

    protected function process()
    {
        //TODO: Check order of parameters
        //1. parameters from current URL
        if ($this->keep_request_params) {
            foreach ($_GET as $key => $param) {
                if (!isset($this->parameters[$key])) {
                    $this->addParameter(new URLParameter($key, $param));
                }
            }
        }

        //2. static parameters from passed href

        $static_pairs = explode("&", $this->script_query);
        foreach ($static_pairs as $pos => $pair) {
            $param_name = $pair;
            $param_value = "";
            if (strpos($pair, "=") !== false) {
                list($param_name, $param_value) = explode("=", $pair);
            }
            if (strlen($param_name) > 0) {
                $this->addParameter(new URLParameter($param_name, $param_value));
            }
        }

        //clear parameters from the Paginator
        if (strlen($this->script_name) > 0 || $this->clear_page_param) {

            Paginator::clearPageFilter($this->parameters);
        }

//        $ret = $this->script_name . queryString($params);
//        if (is_array($row)) {
//            foreach ($row as $param_name => $value) {
//                $ret = str_replace("%" . $param_name . "%", $value, $ret);
//            }
//        }
//
//        if (strrpos($ret, "&") === strlen($ret) - 1) {
//            $ret = substr_replace($ret, "", -1);
//        }

    }

    public function setHref(string $href)
    {
        if (stripos($href, "javascript:") !== false) {

            $this->script_name = $href;
            $this->script_query = "";
            return;
        }

        $script_name = $href;
        $script_query = "";

        if (strpos($script_name, "?") !== false) {
            list($script_name, $script_query) = explode("?", $script_name);
        }

        $this->script_name = $script_name;
        $this->script_query = $script_query;

    }

}