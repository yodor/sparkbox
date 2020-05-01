<?php
include_once("lib/beans/IDataBean.php");

abstract class ArrayDataBean implements IDataBean
{

    protected $values = array();
    protected $pos_map = array();
    protected $fields = array();
    //primary key
    protected $key = NULL;

    protected $filter_field = "";
    protected $filter_value = "";

    protected $iterator = NULL;

    public function __construct()
    {
        $this->initFields();
        $this->initValues();
        $this->constructPositionMap();
    }

    protected abstract function initValues();

    protected abstract function initFields();

    protected function constructPositionMap()
    {
        $this->pos_map = array();

        foreach ($this->values as $pos => $row) {
            $this->pos_map[$row[$this->key]] = $pos;
        }
    }

    public function findValue($field_name, $value)
    {
        $found = false;
        $ret = array();
        if (!in_array($field_name, $this->fields)) throw new Exception("Field [$field_name] not found in this selector. Available fields: " . implode(",", $this->fields));

        $row = array();
        for ($a = 0; $a < count($this->values); $a++) {
            $row = $this->values[$a];
            if (strcmp($row[$field_name], $value) == 0) {
                $found = true;
                $ret = $row;
                break;
            }
        }
        if ($found) return $row;

        return false;
    }

    //IDataBean
    public function key()
    {
        return $this->key;
    }

    public function getCount() : int
    {
        return $this->iterator->count();
    }

    public function getByID(int $id)
    {
        if (!isset($this->pos_map[$id])) throw new Exception("ID not found");

        $pos = $this->pos_map[$id];

        if (!isset($this->values[$pos])) throw new Exception("Value not found");

        $row = $this->values[$pos];

        return $row;
    }

    public function fields() : array
    {
        return $this->fields;
    }

    public function startIterator($filter = "", $fields = "")
    {
        $this->iterator = new ArrayIterator($this->values);
    }

    public function startFieldIterator($filter_field, $filter_value)
    {
        if (!in_array($filter_field, $this->fields)) throw new Exception("Filter field '$filter_field' not found in this source");

        $this->filter_field = $filter_field;
        $this->filter_value = $filter_value;
    }

    public function fetchNext(array &$row, $iterator = false) : bool
    {
        $ret = $this->iterator->valid();
        $row = array();

        if ($ret === TRUE) {
            if ($this->filter_value) {

                $found = false;
                while (!$found && $this->iterator->valid()) {
                    $row = $this->iterator->current();
                    if (strcmp($row[$this->filter_field], $this->filter_value) != 0) {
                        $this->iterator->next();
                    }
                    else {
                        $found = true;
                        $this->iterator->next();
                    }
                }
                $ret = $found;

            }
            else {
                $row = $this->iterator->current();
                $this->iterator->next();
            }

        }
        return $ret;
    }

    public function getByRef($refKey, $refID)
    {
        $ret = false;

        foreach ($this->values as $pos => $row) {
            if (strcmp($row[$refKey], $refID) == 0) {
                $ret = $row;
                break;
            }
        }
        return $ret;
    }

    public function deleteID(int $id)
    {
        if (!isset($this->pos_map[$id])) return false;
        $pos = $this->pos_map[$id];
        if (isset($this->values[$pos])) unset($this->values[$pos]);

        unset($this->pos_map[$id]);
        return true;
    }

    public function deleteRef($refkey, $refval)
    {
        throw new Exception("Not implemented");
    }

    public function haveField(string $field_name) : bool
    {
        return (in_array($field_name, $this->fields));
    }


}

?>
