<?php
include_once("input/DataInput.php");
include_once("input/renderers/ArrayField.php");

class ArrayDataInput extends DataInput
{

    public const string ERROR_TEXT = "This input collection have errors";

    public bool $source_label_visible = false;

    //exception on processing validation or processing
    protected string $error_generic = "";

    public function __construct(string $name, string $label, bool $required)
    {
        parent::__construct($name, $label, $required);

        $this->value = array();
        $this->error = array();
    }



    /**
     * Set generic error text for the whole array input
     * @param string $err
     * @return void
     */
    public function setError(string $err) : void
    {
        //validators or processors might set generic error for the whole array here
        $this->error_generic = $err;
    }

    /**
     * Get the generic error text
     * @return string
     */
    public function getError() : string
    {
        return $this->error_generic;
    }

    public function setValidator(IInputValidator $validator) : void
    {
        parent::setValidator(new ArrayInputValidator($validator));
    }

    public function getValueAt($idx)
    {
        return $this->value[$idx];
    }

    public function setValueAt($idx, $value) : void
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

    public function getErrorAt($idx) : string
    {
        if (isset($this->error[$idx])) return $this->error[$idx];
        return "";
    }

    /**
     * Set or clear error for given index
     * Clear if $err parameter is ""
     * @param int $idx
     * @param string $err
     * @return void
     */
    public function setErrorAt(int $idx, string $err) : void
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

    public function haveError(): bool
    {
        if (count($this->error) > 0 || $this->error_generic) return TRUE;
        return FALSE;
    }

    public function haveErrorAt($idx) : bool
    {
        return isset($this->error[$idx]);
    }

    public function clear() : void
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
        //default error for that label itself or when no element
        $error_text = tr(ArrayDataInput::ERROR_TEXT);

        if (is_array($this->error)) {
            foreach ($this->error as $idx => $error) {
                $error_text.= "<BR>[$idx]: ".$this->getErrorAt($idx);
            }
            return $error_text;
        }
        else {
            //can be string for empty array values return the default error set from validator
            return $this->error_generic;
        }

    }

}

?>
