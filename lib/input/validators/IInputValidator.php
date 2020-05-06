<?php
include_once("input/DataInput.php");

interface IInputValidator
{
    /**
     * Validate value of DataInput is correct
     * @param DataInput $input
     * @return mixed
     */
    public function validate(DataInput $input);

}