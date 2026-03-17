<?php
include_once ("utils/IDataResultProcessor.php");

class URLParameter implements IDataResultProcessor
{
    protected string $name = "";
    protected string $value = "";

    public function __construct(string $name, ?string $value = "", bool $is_slug = false)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function value(): string
    {
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

    public function isResource() : bool
    {
        return str_starts_with($this->name, "#");
    }

    public function setData(array $data) : void
    {
        if ($this->isResource()) {
            //parameterized resource ie ?prodID=385#ProductPhotosBean.%ppID%
            //make value equal to #ProductPhotosBean.34 if ppID is found as data key in $data
            $names = array_keys($data);
            $parametrized = $this->name;
            foreach ($names as $idx=>$name) {
                $replace = array("%$name%"=>$data[$name]);
                $parametrized = strtr($parametrized, $replace);
            }
            $this->value = $parametrized;
        }
    }

}