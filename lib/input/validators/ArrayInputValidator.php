<?php

class ArrayInputValidator implements IInputValidator
{
    protected $item_validator = NULL;

    public function __construct(IInputValidator $validator_private)
    {
        $this->item_validator = $validator_private;
    }

    public function validate(DataInput $input)
    {
        //input processor passes ordered values to the field
        //from arrayinputprocessor
        // 	      $values_ordered = reorderArray($values_array);
        //
        // 	      $field->setValue($values_ordered);
        if (! ($input instanceof ArrayDataInput)) throw new Exception("Not an instance of ArrayDataInput");

        debug("Validating values of ArrayDataInput: '{$input->getName()}'");

        $values_array = $input->getValue();

        if (is_array($values_array)) {

            $values_array = array_values($values_array);
            $input->setValue($values_array);

        }

        $values_array = $input->getValue();

        if (!is_array($values_array) || count($values_array) < 1) {
            if ($input->isRequired()) {
                throw new Exception("Input value(s) for this collection");
            }
        }

        for ($idx = 0; $idx < count($values_array); $idx++) {

            $input->setErrorAt($idx, "");

            $value = $values_array[$idx];

            debug("Value[$idx] - Type: " . getType($value));

            try {
                $cfield = clone $input;

                $cfield->setValue($value);

                //array inputs required?
                //$cfield->setRequired($this->validator_private->require_array_value);

                $this->item_validator->validate($cfield);

                //set value back to the original array as upload validator changes type of storage object
                $input->setValueAt($idx, $cfield->getValue());

            }
            catch (Exception $e) {

                debug("Validate result: ".$e->getMessage());
                $input->setErrorAt($idx, $e->getMessage());

            }

        }


    }

    public function getItemValidator()
    {
        return $this->item_validator;
    }

}

?>