<?php
include_once("sql/ExpressionBuilder.php");

class FromExpression extends ExpressionBuilder
{


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Append " " + $text to the internal buffer
     * @param string $text
     * @return $this
     * @throws Exception
     */
    public function append(string $text) : self
    {
        if (strlen(trim($text)) < 1) throw new Exception("Text can not be empty");
        $this->buffer .= " ".$text;
        return $this;
    }

    /**
     * Set expression value to \$expr
     * Return instance to self
     * If \$expr is null do nothing and return instance only
     *
     * @param string|null $expr
     * @return $this
     * @throws Exception
     */
    public function expr(?string $expr=null) : self
    {
        if (!is_null($expr)) {
            if (strlen(trim($expr))==0) throw new Exception("Expression must be a valid string");
            $this->buffer = trim($expr);
        }
        return $this;
    }

    /**
     * Append " " + "LEFT JOIN" + $joinExpr to the internal buffer
     * @param string $joinExpr
     * @return $this
     * @throws Exception
     */
    public function leftJoin(string $joinExpr) : self
    {
        return $this->appendKeyword("LEFT JOIN", $joinExpr);
    }

    /**
     * Append " " + "RIGHT JOIN" + $joinExpr to the internal buffer
     * @param string $joinExpr
     * @return $this
     * @throws Exception
     */
    public function rightJoin(string $joinExpr) : self
    {
        return $this->appendKeyword("RIGHT JOIN", $joinExpr);
    }

    /**
     * Append " " + "JOIN" + $joinExpr to the internal buffer
     * @param string $joinExpr
     * @return $this
     * @throws Exception
     */
    public function join(string $joinExpr) : self
    {
        return $this->appendKeyword("JOIN", $joinExpr);
    }

    /**
     * Append " " + "INNER JOIN" + $joinExpr to the internal buffer
     * @param string $joinExpr
     * @return $this
     * @throws Exception
     */
    public function innerJoin(string $joinExpr) : self
    {
        return $this->appendKeyword("INNER JOIN", $joinExpr);
    }

    /**
     * Append " " + "ON" + $joinExpr to the internal buffer
     * @param string $joinExpr
     * @return $this
     * @throws Exception
     */
    public function on(string $joinExpr) : self
    {
        return $this->appendKeyword("ON", $joinExpr);
    }

}