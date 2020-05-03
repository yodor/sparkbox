<?php
include_once("lib/iterators/IDataIterator.php");

class ArrayDataIterator implements IDataIterator
{

    protected $key = "id";
    protected $value_key = "value";

    protected $pos = -1;

    public function __construct(array $arr, string $prkey = "id", string $value_key = "value")
    {

        $this->key = $prkey;
        $this->value_key = $value_key;

        $this->values = array();

        foreach ($arr as $key => $val) {
            $this->values[] = array($this->key => $key, $this->value_key => $val);
        }
    }

    public function exec() : int
    {
        return count($this->values);
    }

    public function key() : string
    {
        return $this->key;
    }

    public function count() : int
    {
        return count($this->values);
    }

    public function name() : string
    {
        return "";
    }

    public function next()
    {
        $this->pos++;
        if (isset($this->values[$this->pos])) {
            return $this->values[$this->pos];
        }
        return null;
    }

    public static function FromSelect(SQLSelect $qry, $prkey, $label)
    {
        $db = DBDriver::Get();

        $res = $db->query($qry->getSQL());
        if (!$res) throw new Exception ($db->getError());
        $arr = array();
        while ($row = $db->fetch($res)) {

            $arr_key = $row[$prkey];
            $arr_val = $row[$label];

            if (is_null($arr_key)) {
                $arr_key = "NULL";
                $arr_val = "NULL";
            }

            $arr[$arr_key] = $arr_val;
        }
        $db->free($res);

        return new ArrayDataIterator($arr, $prkey, $label);
    }
}

?>
