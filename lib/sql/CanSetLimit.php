<?php
include_once("sql/LimitExpression.php");

trait CanSetLimit {


    public function limitClear() : void
    {
        $this->_limit->empty();
    }
    /**
     * Set the limit result count number
     * @param int $count
     * @return void
     */
    public function limit(int $count) : void
    {
        $this->_limit->set($count, null);
    }

    public function isLimited() : bool
    {
        return $this->_limit->isEmpty();
    }

}