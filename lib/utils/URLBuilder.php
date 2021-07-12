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
    protected $clear_params = array();

    //protected $keep_request_params = TRUE;

    protected $is_script = FALSE;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->build_string = "";

        $this->script_name = "";
        $this->script_query = "";

        $this->domain = "";
        $this->protocol = "";

        $this->parameters = array();

        $this->resource = "";

        $this->clear_page_param = FALSE;

        $this->is_script = FALSE;

    }
    public function isEmpty() : bool
    {
        return (count($this->parameters) < 1 && strlen($this->script_name) < 1);
    }

    //remove paginator page=? parameter from this href
    public function setClearPageParams(bool $mode)
    {
        $this->clear_page_param = $mode;
    }
    public function setClearParams(string ...$params)
    {
        $this->clear_params = $params;
    }

    //    /**
    //     * prependRequestParams
    //     *
    //     * @param boolean $mode If set to true will prepend the current request query parameters to this link href
    //     */
    //    public function setKeepRequestParams(bool $mode)
    //    {
    //        $this->keep_request_params = $mode;
    //    }

    public function add(URLParameter $param)
    {
        $this->parameters[$param->name()] = $param;
    }

    /**
     * Remove the parameter '$name' from this url builder
     * @param string $name
     */
    public function remove(string $name)
    {
        if (!$this->contains($name)) return;
        unset($this->parameters[$name]);
    }

    public function get(string $name): URLParameter
    {
        return $this->parameters[$name];
    }

    public function contains(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }

//    /**
//     * Return the value of parameter '$name'
//     * @param string $name
//     * @return string
//     */
//    public function getValue(string $name): string
//    {
//        if (!$this->contains($name)) return "";
//        return $this->getParameter($name)->value();
//    }
//
//    /**
//     * Set the value of parameter '$name' to '$value'
//     * @param string $name
//     * @param string $value
//     */
//    public function setValue(string $name, string $value)
//    {
//        $this->addParameter(new URLParameter($name, $value));
//    }


    public function url(): string
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

    public function getScriptName() : string
    {
        return $this->script_name;
    }

    public function setScriptName(string $value)
    {
        $this->script_name = $value;
    }

    public function getScriptPath() : string
    {
        return dirname($this->script_name);
    }

    protected function processQuery()
    {

        //        if ($this->keep_request_params) {
        //            foreach ($_GET as $key => $param) {
        //                if (!isset($this->parameters[$key])) {
        //                    $this->addParameter(new URLParameter($key, $param));
        //                }
        //            }
        //        }

        //clear parameters from the Paginator
        if ($this->clear_page_param) {

            Paginator::clearPageFilter($this->parameters);
        }

        if (count($this->parameters) > 0) {


            foreach ($this->clear_params as $val) {
                if (array_key_exists($val, $this->parameters)) {
                    unset($this->parameters[$val]);
                }
            }

            $names = array_keys($this->parameters);

            $pairs = array();
            foreach ($names as $pos => $name) {
                $param = $this->get($name);
                if ($param->isResource()) continue;
                $pairs[] = $param->text();
            }

            $this->script_query = implode("&", $pairs);

            foreach ($names as $pos => $name) {
                $param = $this->get($name);
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
        $this->reset();

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
            $this->add(new URLParameter("#" . $resource));
        }

        //copy current query parameters if script name is not set - ie local page request
        if (strlen($script_name) < 1) {
            foreach ($_GET as $key => $param) {
                $this->add(new URLParameter($key, $param));
            }
        }

        //overwrite with pairs from the $build_string
        $static_pairs = explode("&", $script_query);
        foreach ($static_pairs as $pos => $pair) {
            $param_name = $pair;
            $param_value = "";
            if (strpos($pair, "=") !== FALSE) {
                list($param_name, $param_value) = explode("=", $pair);
            }
            if (strlen($param_name) > 0) {
                $this->add(new URLParameter($param_name, $param_value));
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
            $param = $this->get($name);
            $param->setData($row);
        }

    }

}