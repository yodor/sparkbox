<?php
include_once ("utils/IDataResultProcessor.php");

class URLParameter implements IDataResultProcessor
{
    protected string $name = "";
    protected string $value = "";
    protected string $field = "";

    protected bool $is_slug = false;

    public function __construct(string $name, ?string $value = "", bool $is_slug = false)
    {
        $this->name = $name;

        //data parameter - parse field name
        if (str_starts_with($value, "%") && str_ends_with($value, "%")) {
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

    public function setValue(string $value) : void
    {
        $this->value = $value;
    }
    public function name(): string
    {
        return $this->name;
    }

    public function field() : string
    {
        return $this->field;
    }

    public function isResource() : bool
    {
        return str_starts_with($this->name, "#");
    }

    /**
     * Return concatenation of name=value string
     * @param bool $quoteValue quote result in single quotes if true
     * @return string
     */
    public function text(bool $quoteValue = FALSE) : string
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

        //is resource ?
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

    public function setSlugEnabled(bool $mode) : void
    {
        $this->is_slug = $mode;
    }
}
