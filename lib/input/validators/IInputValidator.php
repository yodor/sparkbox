<?php
include_once("input/DataInput.php");

interface IInputValidator
{

    /**
     * @param DataInput $input
     * @return void
     */
    public function validate(DataInput $input) : void;

}
