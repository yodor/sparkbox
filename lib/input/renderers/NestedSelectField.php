<?php
include_once("input/renderers/SelectField.php");
include_once("beans/NestedSetBean.php");

class NestedSelectField extends SelectField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function renderItems()
    {

        $path = array();

        $source_key = $this->iterator->key();

        while ($row = $this->iterator->next()) {

            $nodeID = $row[$source_key];

            $lft = $row["lft"];
            $rgt = $row["rgt"];

            trbean($nodeID, $this->getItemRenderer()->getLabelKey(), $row, $this->iterator->name());

            while (count($path) > 0 && $lft > $path[count($path) - 1]) {
                array_pop($path);
            }

            $path[] = $rgt;
            $path_len = count($path);
            $margin = 3 * ($path_len);

            $selected = ($nodeID == $this->input->getValue());

            $item = $this->item;
            $item->setID($nodeID);

            $item->setName($this->input->getName() . "[]");
            $item->setData($row);

            $item->setSelected($selected);

            $label = implode('', array_fill(0, $margin, '&nbsp;')) . $item->getLabel();
            $item->setLabel($label);

            $item->render();

        }

    }

}

?>