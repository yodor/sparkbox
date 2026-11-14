<?php
include_once("beans/DBTableBean.php");

class ItemsBean extends DBTableBean
{
    public function __construct()
    {
        parent::__construct("items");
    }
}
