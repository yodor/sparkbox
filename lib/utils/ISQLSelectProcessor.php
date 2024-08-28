<?php
include_once("sql/SQLSelect.php");
interface ISQLSelectProcessor
{
    public function setSQLSelect(SQLSelect $select) : void;
    public function getSQLSelect() : ?SQLSelect;
}
?>
