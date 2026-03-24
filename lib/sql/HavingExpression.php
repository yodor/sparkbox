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
        if (!$this->isEmpty()) {
            $this->appendKeyword("AND", $expr);
        }
        else {
            $this->buffer = $expr;
        }
        return $this;
    }

}