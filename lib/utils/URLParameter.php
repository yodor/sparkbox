<?php
include_once ("utils/IDataResultProcessor.php");

class URLParameter implements IDataResultProcessor
{
    protected $name;
    protected $value;
    protected $field;

    public function __construct(string $name, string $value = "")
    {
        $this->name = $name;

        if (strpos($value, "%") === 0 && strrpos($value, "%")===0) {
            // It starts with 'http'
            $this->field = substr($value, 1, strlen($value)-1);
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function field()
    {
        return $this->field;
    }

    public function isResource() {
        if (strpos($this->name, "#")===0) {
            return true;
        }
        return false;
    }
    public function text(bool $quoteValue = FALSE)
    {

        $ret = $this->name;
        if ($this->value) {
            $ret .= "=";
            if ($quoteValue) $ret .= "'";
            $ret .= $this->value;
            if ($quoteValue) $ret .= "'";
        }
        return $ret;
    }

    public function setData(array &$data)
    {
        if ($this->field) {
            if (isset($data[$this->field])) {
                $this->value = $data[$this->field];
            }
        }

        if (strpos($this->name, "#")===0) {
            $names = array_keys($data);
            foreach ($names as $idx=>$name) {
                $replace = array("%$name%"=>$data[$name]);
                $this->value = strtr($name, $replace);
            }
        }

    }

}