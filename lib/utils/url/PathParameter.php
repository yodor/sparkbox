<?php
include_once("utils/url/DataParameter.php");

class PathParameter extends DataParameter
{
    protected bool $transliterate = false;

    public function __construct(string $name, string $field = "", bool $transliterate=true)
    {
        parent::__construct($name, $field);
        $this->transliterate=$transliterate;
    }

    public function value(bool $quoted=false): string
    {
        $value = parent::value($quoted);
        if ($this->transliterate) {
            $value = transliterate($value);
        }
        return $value;
    }
}
?>