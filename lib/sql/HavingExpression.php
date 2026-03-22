<?php
include_once("sql/ExpressionBuilder.php");

class HavingExpression extends ExpressionBuilder {

    /**
     * Append (using AND keyword) or initialize the internal buffer with value of $expr
     *
     * @param string $expr
     * @return $this
     * @throws Exception
     */
    public function and(string $expr) : self
    {
        if (strlen(trim($expr))>0) throw new Exception("Expression empty");

        if (!$this->isEmpty()) {
            $this->appendKeyword("AND", $expr);
        }
        else {
            $this->buffer = $expr;
        }
        return $this;
    }

}