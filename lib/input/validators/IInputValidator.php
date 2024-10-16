<?php
include_once("input/DataInput.php");

interface IInputValidator
{

    /**
     * Validate DataInput value and throw exception on error
     * @param DataInput $input
     * @return void
     * @throws
     */
    public function validate(DataInput $input) : void;

}

?>
