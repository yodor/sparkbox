<?php
include_once("beans/DBTableBean.php");

class ContactsBean extends DBTableBean
{
    public function __construct()
    {
        parent::__construct("contacts");
    }
}
