<?php
include_once("utils/url/URLParameter.php");
include_once("utils/url/DataParameter.php");
include_once("utils/url/PathParameter.php");

include_once("utils/Paginator.php");
include_once("utils/IGETConsumer.php");


class URL implements IGETConsumer, IDataResultProcessor
{

    protected bool $is_script = FALSE;

    //keep the original script_name using placeholders intact ie value is javascript:item(%key1%)
    protected string $script_name = "";
    //setData applied on script_name fills this variable ie value is javascript:item(123)
    protected string $script_name_data = "";

    protected string $domain = "";
    protected string $protocol = "";

    /**
     * @var array  All url parameters name/value pairs
     */
    protected array $parameters = array();

    protected bool $clear_page_param = FALSE;
    protected array $clear_params = array();

    /**
     * Construct URL with value current request URL
     * Uses currentURL() internally
     * @return URL
     */
    public static function Current() : URL
    {
        return new URL(currentURL());
    }

    public function __construct(?string $url="")
    {
        if (strlen($url)>0) {
            $this->fromString($url);
        }
        else {
            $this->reset();
        }
    }

    public function reset() : void
    {
        //URL = > http://domain.com/some/other/script.php?param1=1&param2=2#atresource
        //protocol - http; domain - domain.com; script_name /some/other/script.php
        //parameters(param1=>1, param2=>2), resource = atresource
        //
        $this->script_name = "";

        $this->parameters = array();

        $this->clear_params = array();
        $this->clear_page_param = false;
        $this->is_script = false;

        $this->domain = "";
        $this->protocol = "";

    }

    //remove paginator page=? parameter from this href
    public function setClearPageParams(bool $mode) : void
    {
        $this->clear_page_param = $mode;
    }

    public function setClearParams(...$params) : void
    {
        $this->clear_params = $params;
    }

    /**
     * Add/Set query parameter
     * @param URLParameter $param
     */
    public function add(URLParameter $param) : void
    {
        $this->parameters[$param->name()] = $param;
    }

    /**
     * Remove the parameter from this url builder
     * @param string $name Name of parameter to remove
     */
    public function remove(string $name) : void
    {
        if (!$this->contains($name)) return;
        unset($this->parameters[$name]);
    }


    /**
     * Get URLParamter with name $name
     * @param string $name
     * @return URLParameter|null
     */
    public function get(string $name): ?URLParameter
    {
        if (isset($this->parameters[$name])) return $this->parameters[$name];
        return null;
    }

    /**
     * Check if this url contains query paramter named $name
     * @param string $name The query parameter to check for
     * @return bool True if this url string have parameter with name $name
     */
    public function contains(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * Return all query parameter names
     * @return array
     */
    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }


    /**
     * Return string representation of this URL object
     * @return string
     */
    public function toString(): string
    {
        if ($this->is_script) {
            //return the data applied to script name
            if ($this->script_name_data) return $this->script_name_data;
            //return original version
            return $this->script_name;
        }

        $parameters = $this->parameters;

        //clear parameters from the Paginator
        if ($this->clear_page_param) {
            $paginator_parameters = Paginator::Instance()->getParameterNames();
            foreach($paginator_parameters as $name) {
                if (array_key_exists($name, $parameters)) unset($parameters[$name]);
            }
        }

        $script_query = "";
        $resource = "";

        if (count($parameters) > 0) {
            //remove parameter names found in the clear_params array
            foreach ($this->clear_params as $key) {
                if (array_key_exists($key, $parameters)) unset($parameters[$key]);
            }

            $names = array_keys($parameters);
            //consctruct pairs to be imploded using &
            $pairs = array();
            foreach ($names as $idx => $name) {
                $param = $this->get($name);
                if ($param instanceof PathParameter) continue;
                if ($param->isResource()) {
                    //handle paramterized resource named using #resource.%param%
                    //parameter value is done in setData of URLParameter
                    $resource = (strlen($param->value())<1) ? $param->name() : $param->value();
                }
                else {
                    $pairs[] = $param->text();
                }
            }

            $script_query = implode("&", $pairs);
        }

        $result = $this->script_name;
        //sluged parameters
        foreach ($parameters as $idx => $parameter) {
            if ($parameter instanceof PathParameter) {
                $result.= $parameter->value()."/";
            }
        }

        if (strlen($script_query)>0) {
            $result .= "?";
            $result .= $script_query;
        }

        if (strlen($resource)>0) {
            $result .= $resource;
        }

        return $result;
    }

    public function __toString() : string
    {
        return $this->toString();
    }
    /**
     * Full url including protocol and domain
     * Uses fullURL() from functions
     * @return URL
     */
    public function fullURL() : URL
    {
        return new URL(fullURL($this));
    }

    public function getScriptName() : string
    {
        return $this->script_name;
    }

    public function setScriptName(string $value) : void
    {
        $this->script_name = $value;
    }

    public function getScriptPath() : string
    {
        return dirname($this->script_name);
    }

//    public static function Slugify(URL $url) : URL
//    {
//        if ($url->is_script) {
//            return $url;
//        }
//
//        $slug = $url->script_name;
//
//        $slug = str_replace(".php", "/", $slug);
//
//        foreach ($url->parameters as $name => $param) {
//            if ($param instanceof URLParameter) {
//                $slug.= $param->value()."/";
//            }
//        }
//
//        return new URL($slug);
//    }
    /**
     * Reset this url removing any existing data and rebuild using build_string
     * @param string $build_string
     * @return void
     */
    public function fromString(string $build_string) : void
    {

        $this->reset();

        $build_string = trim($build_string);

        if (strlen($build_string)<1) return;

        if (str_starts_with($build_string, "javascript:")) {
            $this->is_script = TRUE;
            $this->script_name = $build_string;
            return;
        }

        //TODO: some urls are relateive
        //"?cmd=copy_product$href_add" - should take only parameters from this string
        //"banners/list.php?itemID=123" - should take only path of current URL without the script file

        //cut domain and protocol first
//        if (str_contains($build_string, "://")) {
//            list($this->protocol, $build_string) = explode("://", $build_string);
//        }
//
//        //first position of '/'
//        $pos = strpos($build_string, "/");
//        $this->domain = substr($build_string, 0,  $pos);
//
//        if ($pos>0) {
//            //from first position of '/' to the end
//            $build_string = substr($build_string, $pos);
//        }

        $resource_param = null;
        //have #resource
        if (str_contains($build_string, "#")) {
            list($build_string, $resource) = explode("#", $build_string);
            if (isset($resource) && strlen($resource) > 0) {
                $resource_param = new URLParameter("#$resource");
            }
        }

        $script_query = "";
        $script_name = $build_string;

        if (str_contains($build_string, "?")) {
            //overwrite
            list($script_name, $script_query) = explode("?", $build_string);
        }

        $this->script_name = $script_name;

        //if (!$this->script_name)throw new Exception("Emptry Script name");

        //TODO: Check usage is correct during buildFrom
        //copy current query parameters if script name is not set - ie local page request
//        if (strlen($this->script_name) < 1) {
//            foreach ($_GET as $key => $param) {
//                $this->add(new URLParameter($key, $param));
//            }
//        }

        //overwrite with pairs from the $build_string
        $static_pairs = explode("&", $script_query);
        foreach ($static_pairs as $idx => $pair) {
            if (strlen(trim($pair))<1) continue;
            $param_name = $pair;
            $param_value = "";
            if (str_contains($pair, "=")) {
                list($param_name, $param_value) = explode("=", $pair);
            }
            if (strlen(trim($param_name))>0) {
                $this->add(new URLParameter($param_name, $param_value));
            }
        }

        //finally append the resource if any
        if ($resource_param instanceof URLParameter) $this->add($resource_param);

    }

    /**
     * @param array $data Parametrise this URL parameter values using $data associative array as source.
     * JavaScript code is replaced using %parameter_name% as a match.
     * $data[$parameter_name] value is used as a replacement.
     *
     */
    public function setData(array $data) : void
    {
        //
        if ($this->is_script) {
            if (str_contains($this->script_name, "%")) {
                $this->script_name_data = "";
                $from = $this->script_name;
                $names = array_keys($data);
                foreach ($names as $idx => $name) {
                    $replace = array("%" . $name . "%" => $data[$name]);
                    $from = strtr($from, $replace);
                }
                $this->script_name_data = $from;
            }
            return;
        }

        $names = array_keys($this->parameters);
        foreach ($names as $name) {
            $param = $this->get($name);
            $param->setData($data);
        }

    }

    public function copyParametersTo(URL $url, bool $overwrite = true) : void
    {
        $parameters = $this->getParameterNames();
        foreach ($parameters as $idx=>$name) {
            if ($url->contains($name) && !$overwrite) continue;
            $url->add($this->get($name));
        }

    }

    public function copyParametersFrom(URL $url, bool $overwrite = true) : void
    {
        $parameters = $url->getParameterNames();
        foreach ($parameters as $idx=>$name) {
            if ($this->contains($name) && !$overwrite) continue;
            $this->add($url->get($name));
        }

    }
    public function clearParameters() : void
    {
        $this->parameters = array();
    }

}
