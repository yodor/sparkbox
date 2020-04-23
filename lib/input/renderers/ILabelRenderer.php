<?php
include_once("lib/input/DataInput.php");

interface ILabelRenderer
{

    public function renderLabel(DataInput $field, int $render_index = -1);


}

?>