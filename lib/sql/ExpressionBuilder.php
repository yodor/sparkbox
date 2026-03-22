<?php

class ExpressionBuilder {

    protected string $buffer = "";

    public function __construct()
    { }

    public function isEmpty() : bool
    {
        return (strlen(trim($this->buffer))==0);
    }

    public function clear() : void
    {
        $this->buffer = "";
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
     * Helper method to append to the internal buffer
     * Appends " " + $keyword + " " + $fromExpr to the internal buffer
     * @param string $keyword
     * @param string $expr
     * @return $this
     * @throws Exception
     */
    protected function appendKeyword(string $keyword, string $expr) : self
    {
        if (strlen(trim($expr)) < 1) throw new Exception("Keyword can not be empty");
        if (strlen(trim($keyword)) < 1) throw new Exception("Expression can not be empty");

        $this->buffer .= " $keyword ".$expr;
        return $this;
    }
}