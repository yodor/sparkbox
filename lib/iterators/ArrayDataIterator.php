<?php
include_once("iterators/IDataIterator.php");

class ArrayDataIterator implements IDataIterator
{

    const string KEY_ID = "id";
    const string KEY_VALUE = "value";

    protected string $id_key = ArrayDataIterator::KEY_ID;
    protected string $value_key = ArrayDataIterator::KEY_VALUE;

    protected int $pos = -1;

    protected array $values = array();

    public function __construct(array $items = array(), string $id_key = ArrayDataIterator::KEY_ID, string $value_key = ArrayDataIterator::KEY_VALUE)
    {

        $this->id_key = $id_key;
        $this->value_key = $value_key;

        $this->values = array();

        foreach ($items as $idx => $value) {
            $this->values[] = array($this->id_key => $idx, $this->value_key => $value);
        }
    }

    public function clearValues() : void
    {
        $this->values = array();
    }
    public function appendValue(string $id, string $value) : void
    {
        $this->values[] = array($this->id_key => $id, $this->value_key => $value);
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

    public function isActive() : bool
    {
        return true;
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

    public function next() : ?array
    {
        $this->pos++;
        if (isset($this->values[$this->pos])) {
            return $this->values[$this->pos];
        }
        return NULL;
    }

    public static function FromSelect(SQLSelect $qry, $prkey, $label)
    {
        $db = DBConnections::Open();

        $result = $db->query($qry->getSQL());

        $arr = array();
        while ($row = $result->fetch()) {

            $arr_key = $row[$prkey];
            $arr_val = $row[$label];

            if (is_null($arr_key)) {
                $arr_key = "NULL";
                $arr_val = "NULL";
            }

            $arr[$arr_key] = $arr_val;
        }


        return new ArrayDataIterator($arr, $prkey, $label);
    }

    public function bean(): ?DBTableBean
    {
        return NULL;
    }
}

?>
