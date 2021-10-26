<?php
include_once("input/DataInput.php");
include_once("input/renderers/ArrayField.php");

class ArrayDataInput extends DataInput
{

    public const ERROR_TEXT = "This input collection have errors";

    public function __construct(string $name, string $label, bool $required)
    {
        parent::__construct($name, $label, $required);

        $this->value = array();
        $this->error = array();
    }

    public function setValidator(IInputValidator $validator)
    {
        parent::setValidator(new ArrayInputValidator($validator));
    }

    public function getValueAt($idx)
    {
        return $this->value[$idx];
    }

    public function setValueAt($idx, $value)
    {
        $this->value[$idx] = $value;
    }

    public function getValues(): array
    {
        return $this->value;
    }

    public function getValuesCount(): int
    {
        return count($this->value);
    }

    public function getErrorAt($idx)
    {
        if (isset($this->error[$idx])) return $this->error[$idx];
        return "";
    }

    public function setErrorAt(int $idx, string $err)
    {

        if (strlen($err) > 0) {
            $this->error[$idx] = $err;
        }
        else {
            if (isset($this->error[$idx])) {
                unset($this->error[$idx]);
            }
        }

    }

//    public function getValuesCount()
//    {
//        return count($this->value);
//    }
//
//    public function appendElement($val)
//    {
//        $this->value[] = $val;
//    }

//    public function removeElementAt($idx)
//    {
//
//        if (isset($this->value[$idx])) {
//            unset($this->value[$idx]);
//            $new_vals = array();
//            foreach ($this->value as $key => $val) {
//                $new_vals[] = $val;
//            }
//            $this->value = $new_vals;
//        }
//
//        if (isset($this->error[$idx])) {
//            unset($this->error[$idx]);
//            $new_vals = array();
//            foreach ($this->error as $key => $val) {
//                $new_vals[] = $val;
//            }
//            $this->error = $new_vals;
//        }
//
//    }

    public function haveError(): bool
    {

        if (is_array($this->error)) {
            if (count($this->error) > 0) return TRUE;
        }
        if ($this->error) {
            return TRUE;
        }
        return FALSE;

    }

    public function haveErrorAt($idx)
    {
        return isset($this->error[$idx]);
    }

    public function clear()
    {
        $this->value = array();
        $this->error = array();
    }

    /**
     * Return error text for all values having error
     * @return string
     */
    public function getErrorText() : string
    {
        $error_text = tr(ArrayDataInput::ERROR_TEXT);
        $values_count = count($this->value);

        for ($idx=0; $idx<$values_count; $idx++) {
            if ($this->haveErrorAt($idx)) {
                $error_text.= "<BR>[$idx]: ".$this->getErrorAt($idx);
            }
        }
        return $error_text;
    }

}

?>