<?php
include_once("sql/ISQLGet.php");

class SQLColumn implements ISQLGet
{
    protected string $prefix = "";

    protected string $alias = "";
    protected string $expression = "";

    protected string $name = "";
    protected array|string $value = "";

    public function __construct(string $name = "", array|string $value = "")
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function setName(string $name) : void
    {
        if (strlen(trim($name))<1) throw new Exception("SQLColumn name can not be empty");

        $this->name = trim($name);
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Set column value to $value
     * @param string $value
     * @return void
     */
    public function setValue(string $value) : void
    {
        $this->value = $value;
    }

    /**
     * Append $value to this column values
     * If current column value is empty a new array will be created and $value will be appended to it
     * If current column value is already array, $value will be appended to it
     * @param string $value
     * @return void
     */
    public function addValue(string $value) : void
    {
        if (is_array($this->value)) {
            $this->value[] = $value;
            return;
        }

        if ($this->value) {
            $this->value = array($this->value);
        }
        else {
            $this->value = array();
        }

        $this->value[] = $value;
    }

    public function getValue() : array|string
    {
        return $this->value;
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

    /**
     * Return the sql string for this column
     *
     * @return string
     */
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
        else {
            if (is_array($this->value)) {
                $result .= " = " . implode(";", $this->value);
            } else if (strlen(trim($this->value)) > 0) {
                $result .= " = " . $this->value;
            }
        }
        return $result;
    }
}