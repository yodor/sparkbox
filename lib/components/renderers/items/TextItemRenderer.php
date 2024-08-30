<?php
include_once("components/renderers/items/DataIteratorItem.php");

class TextItemRenderer extends DataIteratorItem
{

    protected $field_name;

    public function __construct(string $field_name)
    {
        parent::__construct();

        $this->field_name = $field_name;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->contents = $data[$this->field_name];
    }

    public function setFieldName(string $field_name)
    {
        $this->field_name = $field_name;
    }

    public function getFieldName(): string
    {
        return $this->field_name;
    }

}

?>
