<?php
include_once("sql/ISQLGet.php");

class SQLColumn implements ISQLGet
{
    protected string $prefix = "";
    protected string $name = "";
    protected string $alias = "";
    protected string $expression = "";

    public function __construct()
    {

    }

    public function setName(string $name) : void
    {
        if (empty($name)) throw new Exception("SQLColumn name can not be empty");
        $this->name = trim($name);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setAlias(string $alias) : void
    {
        $this->alias = trim($alias);
    }

    public function getAlias() : string
    {
        return $this->alias;
    }

    public function setPrefix(string $prefix) : void
    {
        $this->prefix = trim($prefix);
    }

    public function getPrefix() : string
    {
        return $this->prefix;
    }

    public function setExpression(string $expression, string $alias_name) : void
    {
        $alias_name = trim($alias_name);
        if (empty($alias_name)) throw new Exception("SQLColumn alias can not be empty");

        $expression = trim($expression);
        if (empty($expression)) throw new Exception("SQLColumn expression can not be empty");

        $this->expression = $expression;
        $this->name = $alias_name;
        $this->alias = $alias_name;
    }

    public function getSQL() : string
    {
        if ($this->expression) {
            return $this->expression." AS ".$this->alias;
        }

        $result = "";
        if ($this->prefix) {
            $result.= $this->prefix.".";
        }
        $result .= $this->name;
        if ($this->alias) {
            $result .= " AS ".$this->alias;
        }
        return $result;
    }
}

?>
