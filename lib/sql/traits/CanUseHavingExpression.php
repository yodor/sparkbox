<?php
trait CanUseHavingExpression
{

    /**
     * Initialize or append using "AND" the \$expr contents to the internal buffer
     *
     * $stmt->having("status > 0");
     *
     * $stmt->having("status > 0")->and("option = 1");
     *
     * $stmt->having("status > 0");
     * $stmt->having("option > 0");
     *
     * If text is empty it returns just the instance
     *
     * @param string $expr
     * @return HavingExpression Return the HavingExpression itself
     * @throws Exception
     */
    public function having(string $expr) : HavingExpression
    {
        if (strlen(trim($expr))>0) return $this->_having->and($expr);
        return $this->_having;
    }

}