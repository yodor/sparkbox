<?php
include_once("lib/input/renderers/IFieldRenderer.php");

//interface hint
interface IArrayFieldRenderer extends IFieldRenderer
{
    public function renderControls();

    public function renderElementSource();

    public function renderArrayContents();
}

?>