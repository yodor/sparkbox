<?php
include_once("lib/input/DataInput.php");

interface IInputValidator
{
    public function validateInput(DataInput $field);


}