<?php
include_once("lib/beans/ArrayDataBean.php");

class ArraySelector extends ArrayDataBean
{

    protected $arr = NULL;
    protected $value_key = NULL;

    public function __construct(array $arr, string $prkey = "arr_id", string $value_key = "arr_val")
    {
        $this->arr = $arr;
        $this->key = $prkey;
        $this->value_key = $value_key;

        parent::__construct();

    }

    protected function initFields()
    {
        $this->fields = array($this->key, $this->value_key);

    }

    protected function initValues()
    {

        $this->values = array();

        foreach ($this->arr as $key => $val) {
            $this->values[] = array($this->key => $key, $this->value_key => $val);

        }

    }

    public static function FromSelect(SelectQuery $qry, $prkey, $label)
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

        return new ArraySelector($arr, $prkey, $label);
    }
}

?>
