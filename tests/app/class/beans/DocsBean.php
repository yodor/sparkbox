<?php
include_once("beans/DBTableBean.php");

class DocsBean extends DBTableBean
{
    public function __construct()
    {
        parent::__construct("docs");
    }
}
