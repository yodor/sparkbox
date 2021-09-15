<?php

class SparkGlobals
{

    private $defines = array();

    protected $beanLocations = array();

    protected static $instance = null;

    static public function Instance() : SparkGlobals
    {
        if (self::$instance instanceof SparkGlobals) {
            return self::$instance;
        }
        self::$instance = new SparkGlobals();
        return self::$instance;
    }

    private function __construct()
    {

    }

    public function addIncludeLocation(string $class_location)
    {
        $this->beanLocations[] = $class_location;
    }

    public function includeBeanClass(string $class_name)
    {
        debug("Including bean class: $class_name");
        foreach ($this->beanLocations as $pos => $location) {
            $class_file = $location . $class_name . ".php";
            debug("Trying file: ".$class_file);
            @include_once($class_file);
            if (class_exists($class_name, FALSE)) {
                debug("Class load success");
                break;
            }
        }

        if (!class_exists($class_name, FALSE)) {
            debug("Class load failed");
            throw new Exception("Bean class not found: " . $class_name);
        }
    }

    public function set(string $name, string $value)
    {
        $this->defines[$name] = $value;
    }

    public function get(string $name, string $default="") : string
    {
        if (isset($this->defines[$name])) {
            return $this->defines[$name];
        }
        else return $default;

    }


    public function export()
    {
        foreach ($this->defines as $key => $val) {
            if (defined($key)) {
                continue;
            }
            define($key, $val);
        }
    }

    public function dump()
    {
        foreach ($this->defines as $key => $val) {
            echo $key . "=>" . $val;
            echo "<BR>";
        }
    }
}

?>