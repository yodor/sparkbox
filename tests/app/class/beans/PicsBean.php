<?php
include_once("beans/DBTableBean.php");

class PicsBean extends DBTableBean
{
    public function __construct()
    {
        parent::__construct("pics");
    }
}
