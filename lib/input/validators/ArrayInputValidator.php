<?php

class ArrayInputValidator implements IInputValidator
{
    protected $validator_private = NULL;

    public function __construct(IInputValidator $validator_private)
    {
        $this->validator_private = $validator_private;
    }

    public function validateInput(DataInput $field)
    {
        //input processor passes ordered values to the field
        //from arrayinputprocessor
        // 	      $values_ordered = reorderArray($values_array);
        //
        // 	      $field->setValue($values_ordered);


        debug("ArrayInputValidator::validateInput");

        $values_array = $field->getValue();

        if (is_array($values_array)) {

            // 		//case for checkbox arrays and posting empty row
            // 		for ($idx=0;$idx<count($values_array);$idx++) {
            // 		    $values_array[$idx] = isset($values_array[$idx]) ? $values_array[$idx] : array();
            // 		}

            $values_array = array_values($values_array);
            $field->setValue($values_array);

        }


        $values_array = $field->getValue();

        if (!is_array($values_array) || count($values_array) < 1) {
            if ($field->isRequired()) {
                throw new Exception("Input value(s) for this collection");
            }

        }

        debug("ArrayInputValidator::validateInput: field value is array");

        for ($idx = 0; $idx < count($values_array); $idx++) {

            $field->setErrorAt($idx, false);

            $value = $values_array[$idx];

            debug("ArrayInputValidator::validateInput: validating field at position '$idx' - " . getType($value));

            try {
                $cfield = clone $field;

                $cfield->setValue($value);

                //array inputs required?
                $cfield->setRequired(true);

                $this->validator_private->validateInput($cfield);

                //set value back to the original array as upload validator changes type of storage object
                $field->setValueAt($idx, $cfield->getValue());

            }
            catch (Exception $e) {

                debug("ArrayInputValidator::validateInput: Exception at position '$idx' - " . getType($value) . " Setting field error: " . $e->getMessage());
                $field->setErrorAt($idx, $e->getMessage());

            }

        }


    }

    public function getValidatorPrivate()
    {
        return $this->validator_private;
    }
    //   public function validateFinal(InputField $field)
    //   {
    //        $this->validator_private->validateFinal($field);
    //
    //        if ($field->isRequired() && count($field->getValue())<1) throw new Exception("This required field needs value");
    //   }
    //   public function validateFinal(InputField $field)
    //   {
    //       debug("UploadDataValidator::validateFinal: field['".$field->getName()."']");
    //
    //       if ($field->isRequired() && count($field->getValue())<1) {
    // 	  throw new Exception("Select atleast one value for this array ");
    //       }
    //   }
}

?>