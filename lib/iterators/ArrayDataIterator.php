<?php
include_once("iterators/IDataIterator.php");

class ArrayDataIterator implements IDataIterator
{

    const KEY_ID = "id";
    const KEY_VALUE = "value";

    protected $id_key = ArrayDataIterator::KEY_ID;
    protected $value_key = ArrayDataIterator::KEY_VALUE;

    protected $pos = -1;

    public function __construct(array $arr, string $prkey = ArrayDataIterator::KEY_ID, string $value_key = ArrayDataIterator::KEY_VALUE)
    {

        $this->id_key = $prkey;
        $this->value_key = $value_key;

        $this->values = array();

        foreach ($arr as $key => $val) {
            $this->values[] = array($this->id_key => $key, $this->value_key => $val);
        }
    }

    /**
     * Start data iterator and return number of items in this collection
     * @return int
     */
    public function exec(): int
    {
        $this->pos = -1;
        return count($this->values);
    }

    public function key(): string
    {
        return $this->id_key;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function name(): string
    {
        return "";
    }

    public function next()
    {
        $this->pos++;
        if (isset($this->values[$this->pos])) {
            return $this->values[$this->pos];
        }
        return NULL;
    }

    public static function FromSelect(SQLSelect $qry, $prkey, $label)
    {
        $db = DBConnections::Get();

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

    public function bean(): ?DBTableBean
    {
        return NULL;
    }
}

?>