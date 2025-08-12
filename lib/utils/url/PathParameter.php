<?php
include_once("utils/url/DataParameter.php");

class PathParameter extends DataParameter
{
    protected bool $transliterate = true;
    protected bool $appendPathSeparator = true;

    public function __construct(string $name, string $field = "", bool $transliterate=true, bool $appendPathSeparator=true)
    {
        parent::__construct($name, $field);
        $this->transliterate=$transliterate;
        $this->appendPathSeparator=$appendPathSeparator;
    }

    public function value(bool $quoted=false): string
    {
        $value = parent::value($quoted);
        if ($this->transliterate) {
            $value = transliterate($value);
        }
        return $value;
    }
    public function isAppendPathSeparator(): bool
    {
        return $this->appendPathSeparator;
    }
    public function isTransliterate(): bool
    {
        return $this->transliterate;
    }
}
?>