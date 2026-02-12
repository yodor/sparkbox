<?php
include_once("utils/url/DataParameter.php");

class PathParameter extends DataParameter
{
    protected bool $doSlug = true;
    protected bool $appendPathSeparator = true;

    public function __construct(string $name, string $field = "", bool $doSlug=true, bool $appendPathSeparator=true)
    {
        parent::__construct($name, $field);
        $this->doSlug=$doSlug;
        $this->appendPathSeparator=$appendPathSeparator;
    }

    public function value(bool $quoted=false): string
    {
        $value = parent::value($quoted);
        if ($this->doSlug) {
            $value = Spark::Slugify($value);
        }
        return $value;
    }
    public function isAppendPathSeparator(): bool
    {
        return $this->appendPathSeparator;
    }
    public function isSlug(): bool
    {
        return $this->doSlug;
    }
}