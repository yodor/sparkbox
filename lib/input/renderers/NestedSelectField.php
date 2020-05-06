<?php
include_once("lib/input/renderers/SelectField.php");
include_once("lib/beans/NestedSetBean.php");


class NestedSelectField extends SelectField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function renderItems()
    {

        $field_values = $this->input->getValue();
        $field_name = $this->input->getName();

        $path = array();

        $source_key = $this->iterator->key();

        while ($row = $this->iterator->next()) {

            $lft = $row["lft"];
            $rgt = $row["rgt"];
            $nodeID = $row[$source_key];

            trbean($nodeID, $this->list_label, $row, $this->iterator->name());

            $value = $row[$this->list_key];
            $label = $row[$this->list_label];

            while (count($path) > 0 && $lft > $path[count($path) - 1]) {
                array_pop($path);
            }

            $path[] = $rgt;

            $path_len = count($path);

            $margin = 3 * ($path_len);
            $label = implode('', array_fill(0, $margin, '&nbsp;')) . $label;

            $selected = $this->isModelSelected($value, $field_values);

            $item = clone $this->item;
            $item->setID($nodeID);
            $item->setValue($value);

            $item->setLabel($label);
            $item->setName($field_name . "[]");

            $item->setSelected($selected);

            $item->render();

        }

    }

}

?>