<?php
include_once("lib/input/DataInput.php");
include_once("lib/input/renderers/ArrayField.php");

class ArrayDataInput extends DataInput
{

    public $allow_dynamic_addition = false;

    /**
     * @var IArrayFieldRenderer|null
     */
    protected $array_renderer = NULL;

    public function __construct(string $name, string $label, bool $required)
    {
        parent::__construct($name, $label, $required);

        $this->value = array();
        $this->error = array();

        $this->array_renderer = new ArrayField();
    }

    public function getArrayRenderer()
    {
        return $this->array_renderer;
    }

    public function setArrayRenderer(IArrayFieldRenderer $renderer)
    {
        $this->array_renderer = $renderer;
    }

    public function setValidator(IInputValidator $validator)
    {
        $this->validator = new ArrayInputValidator($validator);
    }

    public function getValueAt($idx)
    {
        return $this->value[$idx];
    }

    public function setValueAt($idx, $value)
    {
        $this->value[$idx] = $value;
    }

    public function getErrorAt($idx)
    {
        if (isset($this->error[$idx])) return $this->error[$idx];
        return "";
    }

    public function setErrorAt($idx, $err)
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

    public function getValuesCount()
    {
        return count($this->value);
    }

    public function appendElement($val)
    {

        $this->value[] = $val;
    }

    public function removeElementAt($idx)
    {

        if (isset($this->value[$idx])) {
            unset($this->value[$idx]);
            $new_vals = array();
            foreach ($this->value as $key => $val) {
                $new_vals[] = $val;
            }
            $this->value = $new_vals;
        }

        if (isset($this->error[$idx])) {
            unset($this->error[$idx]);
            $new_vals = array();
            foreach ($this->error as $key => $val) {
                $new_vals[] = $val;
            }
            $this->error = $new_vals;
        }

    }

    public function haveError()
    {

        if (is_array($this->error)) {
            if (count($this->error) > 0) return true;
        }
        else {
            if (strlen($this->error) > 0) {
                return true;
            }
        }
        return false;

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

}

?>