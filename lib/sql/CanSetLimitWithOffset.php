<?php
include_once("sql/CanSetLimit.php");

trait CanSetLimitWithOffset
{
    use CanSetLimit;

    public function limit(int $count, ?int $offset=null) : void
    {
        $this->_limit->setLimit($count, $offset);
    }

}