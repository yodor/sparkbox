<?php
include_once("input/renderers/SelectField.php");
include_once("beans/NestedSetBean.php");

class NestedSelectField extends SelectField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setClassName("SelectField");
    }

    protected function renderItems() : void
    {

        $this->renderDefaultItem();

        $num = $this->iterator->exec();

        $path = array();

        $source_key = $this->iterator->key();

        $this->item->setName($this->dataInput->getName());

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
            $margin = ($path_len) - 1;

            $selected = ($nodeID == $this->dataInput->getValue());

            $this->item->setKey($nodeID);

            $this->item->setData($row);

            $this->item->setSelected($selected);

            $label = implode('', array_fill(0, $margin, '&emsp;')) . $this->item->getLabel();
            $this->item->setLabel($label);

            $this->item->render();

        }

    }

}

?>
