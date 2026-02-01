<?php

class SparkGlobals
{

    private array $defines = array();

    protected array $beanLocations = array();

    protected static ?SparkGlobals $instance = null;

    static public function Instance() : SparkGlobals
    {
        if (self::$instance == null) {
            self::$instance = new SparkGlobals();
        }
        return self::$instance;
    }

    private function __construct()
    {

    }

    public function addIncludeLocation(string $class_location) : void
    {
        $this->beanLocations[] = $class_location;
    }

    public function includeBeanClass(string $class_name) : void
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

    public function set(string $name, string $value) : void
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


    public function export() : void
    {
        foreach ($this->defines as $key => $val) {
            if (defined($key)) {
                continue;
            }
            define($key, $val);
        }
    }

    public function dump() : void
    {
        foreach ($this->defines as $key => $val) {
            echo $key . "=>" . $val;
            echo "<BR>";
        }
    }
}

?>