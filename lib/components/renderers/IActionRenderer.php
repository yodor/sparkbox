<?php
include_once("actions/Action.php");

interface IActionRenderer
{
    public function setAction(Action $a);
    public function getAction();

    public function setData(array $row);
}

?>