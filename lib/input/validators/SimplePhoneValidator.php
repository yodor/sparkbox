<?php
include_once("input/DataInput.php");
include_once("input/validators/IInputValidator.php");

class SimplePhoneValidator implements IInputValidator
{

    protected bool $allow_plus = TRUE;

    public function __construct(bool $allow_plus = TRUE)
    {
        $this->allow_plus = $allow_plus;
    }
    /**
     * @param DataInput $input
     * @throws Exception
     */
    public function validate(DataInput $input) : void
    {

        $value = $input->getValue();

        if (strlen(trim($value)) == 0 && $input->isRequired()) {
            throw new Exception("Input numeric value");
        }
        $value = str_replace(" ", "", $value);

        if (!preg_match("/^\+?\d+$/",  $value)) throw new Exception("Input numeric value");

    }

}

?>
