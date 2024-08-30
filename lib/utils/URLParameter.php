<?php
include_once ("utils/IDataResultProcessor.php");

class URLParameter implements IDataResultProcessor
{
    protected $name;
    protected $value;
    protected $field;

    protected $is_slug;

    public function __construct(string $name, string $value = "", $is_slug = false)
    {
        $this->name = $name;

        if (str_starts_with($value, "%") && str_ends_with($value, "%")) {
            // It starts with 'http'
            $this->field = substr($value, 1, strlen($value)-1);
        }

        $this->value = $value;
        $this->is_slug = $is_slug;
    }

    public function value(bool $quoted=false): string
    {
        if ($quoted) {
            return "'".$this->value."'";
        }
        return $this->value;
    }

    public function setValue(string $value)
    {
        $this->value = $value;
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

    public function setData(array $data) : void
    {
        if ($this->field) {
            if (isset($data[$this->field])) {
                $this->value = $data[$this->field];
            }
        }

        if (str_starts_with($this->name, "#")) {
            $names = array_keys($data);
            foreach ($names as $idx=>$name) {
                $replace = array("%$name%"=>$data[$name]);
                $this->value = strtr($name, $replace);
            }
        }

    }

    public function isSlugEnabled() : bool
    {
        return $this->is_slug;
    }

    public function setSlugEnabled(bool $mode)
    {
        $this->is_slug = $mode;
    }
}
