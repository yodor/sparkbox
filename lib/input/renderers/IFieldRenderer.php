<?php
include_once("lib/input/DataInput.php");

interface IFieldRenderer
{
    public function setField(DataInput $field);

    public function getField();

    public function renderValue(DataInput $field);

    public function renderField(DataInput $field);

}

?>