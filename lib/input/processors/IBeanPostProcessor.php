<?php
include_once("beans/DBTableBean.php");
include_once("input/DataInput.php");

interface IBeanPostProcessor
{

    public function loadBeanData(int $editID, DBTableBean $bean, array $data) : void;

    public function loadPostData(array $data) : void;

}