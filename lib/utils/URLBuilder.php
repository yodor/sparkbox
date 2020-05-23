<?php
include_once("utils/URLParameter.php");
include_once("utils/Paginator.php");

class URLBuilder
{

    //the original href that was set to this URLBuilder. Can contain parameterized values
    //
    protected $build_string = "";

    protected $script_name = "";
    protected $script_query = "";

    protected $domain = "";
    protected $protocol = "";

    protected $parameters;

    protected $resource = "";

    protected $clear_page_param = FALSE;

    protected $keep_request_params = TRUE;

    protected $is_script = FALSE;

    public function __construct()
    {
        $this->parameters = array();
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

    public function getParameter(string $name): URLParameter
    {
        return $this->parameters[$name];
    }

    public function haveParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }

    public function url()
    {
        if ($this->is_script) {
            return $this->script_name;
        }

        $this->processQuery();

        $ret = $this->protocol . $this->domain . $this->script_name;

        if ($this->script_query) {
            $ret .= "?";
            $ret .= $this->script_query;
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

    protected function processQuery()
    {

        if ($this->keep_request_params) {
            foreach ($_GET as $key => $param) {
                if (!isset($this->parameters[$key])) {
                    $this->addParameter(new URLParameter($key, $param));
                }
            }
        }

        //clear parameters from the Paginator
        if ($this->clear_page_param) {

            Paginator::clearPageFilter($this->parameters);
        }

        if (count($this->parameters) > 0) {

            $names = array_keys($this->parameters);

            $pairs = array();
            foreach ($names as $pos => $name) {
                $param = $this->getParameter($name);
                if ($param->isResource()) continue;
                $pairs[] = $param->text();
            }

            $this->script_query = implode("&", $pairs);

            foreach ($names as $pos => $name) {
                $param = $this->getParameter($name);
                if (!$param->isResource()) continue;
                $this->resource = $param->value();
            }
        }

    }

    public function getBuildFrom(): string
    {
        return $this->build_string;
    }

    public function buildFrom(string $build_string)
    {
        //store the original href. might contain parameterized actions
        $this->build_string = $build_string;

        if (stripos($build_string, "javascript:") !== FALSE) {
            $this->is_script = TRUE;
            $this->script_name = $build_string;
            $this->script_query = "";
            return;
        }

        $script_name = $build_string;
        $script_query = "";

        if (strpos($script_name, "?") !== FALSE) {
            list($script_name, $script_query) = explode("?", $script_name);
        }
        $this->script_name = $script_name;

        if (strpos($script_query, "#") !== FALSE) {
            $resource = "";
            list($script_query, $resource) = explode("#", $script_query);
            $this->addParameter(new URLParameter("#" . $resource));
        }

        $static_pairs = explode("&", $script_query);
        foreach ($static_pairs as $pos => $pair) {
            $param_name = $pair;
            $param_value = "";
            if (strpos($pair, "=") !== FALSE) {
                list($param_name, $param_value) = explode("=", $pair);
            }
            if (strlen($param_name) > 0) {
                $this->addParameter(new URLParameter($param_name, $param_value));
            }
        }

    }

    public function setData(array $row)
    {
        //process javascript hrefs directly
        if ($this->is_script) {

            $from = $this->build_string;
            $names = array_keys($row);
            foreach ($names as $idx => $name) {
                $replace = array("%" . $name . "%" => $row[$name]);
                $from = strtr($from, $replace);
            }
            $this->script_name = $from;
            return;
        }

        $names = array_keys($this->parameters);
        foreach ($names as $idx => $name) {
            $param = $this->getParameter($name);
            $param->setData($row);
        }

    }

}