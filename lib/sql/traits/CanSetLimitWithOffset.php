<?php
include_once("sql/traits/CanSetLimit.php");

trait CanSetLimitWithOffset
{
    use CanSetLimit;

    public function limit(int $count, ?int $offset=null) : void
    {
        $this->_limit->set($count, $offset);
    }

}