<?php
include_once("sql/LimitExpression.php");

trait CanSetLimit {


    public function removeLimit() : void
    {
        $this->_limit->clear();
    }
    /**
     * Set the limit result count number
     * @param int $count
     * @return void
     */
    public function limit(int $count) : LimitExpression
    {
        $this->_limit->setLimit($count, null);
        return $this->_limit;
    }

    public function isLimited() : bool
    {
        return $this->_limit->hasLimit();
    }

}