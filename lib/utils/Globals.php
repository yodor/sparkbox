<?php

class Globals
{

    private $defines = array();

    public function __construct()
    {
    }

    public function set($name, $value)
    {
        $this->defines[$name] = $value;
    }

    public function get($name)
    {
        if (isset($this->defines[$name])) {
            return $this->defines[$name];
        }
        return NULL;
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