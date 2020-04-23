<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/DataInput.php");

class EmptyValueValidator implements IInputValidator
{
    public $require_array_value = false;


    public function validateInput(DataInput $field)
    {

        $value = $field->getValue();

        //checkbox and radios receive array of values here
        if (is_array($value)) {

            if ($field->isRequired()) {
                if (count($value) < 1) throw new Exception("Input value ");
                $empty_count = 0;
                foreach ($value as $idx => $val) {
                    if (strlen(trim($val)) == 0 && $this->require_array_value) $empty_count++;
                }
                if ($empty_count == count($value)) throw new Exception("Input value ");
            }

        }
        else {

            if (strlen(trim($value)) == 0 && $field->isRequired()) {
                throw new Exception("Input value ");
            }

            if ($field->getLinkMode() && strlen(trim($value)) === 0) {
                $link_field = $field->getLinkField();
                $fti = $link_field->getRenderer()->getFreetextInput();
                if (strcmp($fti, $link_field->getValue()) === 0) {
                    throw new Exception("Please specify other");
                }
            }

        }


    }

}

?>