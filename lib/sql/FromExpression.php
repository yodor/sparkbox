<?php

class FromExpression
{
    protected string $buffer = "";

    public function __construct()
    { }

    /**
     * Append " " + $text to the internal buffer
     * @param string $text
     * @return $this
     * @throws Exception
     */
    public function append(string $text) : self
    {
        if (strlen(trim($text)) < 1) throw new Exception("Keyword can not be empty");
        $this->buffer .= " ".$text;
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

    /**
     * Return the contents of the buffer
     * @return string
     */
    public function toString() : string
    {
        return $this->buffer;
    }

    /**
     * Return the contents of the buffer
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * Return instance to self
     * If \$fromExpr is not empty and not null the internal buffer will be initialized with it.
     * @param string|null $fromExpr
     * @return $this
     * @throws Exception
     */
    public function expr(?string $fromExpr=null) : self
    {
        if (!is_null($fromExpr)) {
            if (strlen(trim($fromExpr))==0) throw new Exception("Expression must be a valid string");
            $this->buffer = trim($fromExpr);
        }
        return $this;
    }

    /**
     * Helper method to append to the internal buffer
     * Appends " " + $keyword + " " + $fromExpr to the internal buffer
     * @param string $keyword
     * @param string $fromExpr
     * @return $this
     * @throws Exception
     */
    protected function appendKeyword(string $keyword, string $fromExpr) : self
    {
        if (strlen(trim($fromExpr)) < 1) throw new Exception("Keyword can not be empty");
        if (strlen(trim($keyword)) < 1) throw new Exception("From expr can not be empty");
        $this->buffer .= " $keyword ".$fromExpr;
        return $this;
    }
}