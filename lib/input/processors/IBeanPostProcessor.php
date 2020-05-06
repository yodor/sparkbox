<?php
include_once("beans/DBTableBean.php");
include_once("input/DataInput.php");


interface IBeanPostProcessor
{

    public function loadBeanData(int $editID, DBTableBean $bean, DataInput $input, array &$item_row);

    public function loadPostData(DataInput $input, array &$arr);

}

?>