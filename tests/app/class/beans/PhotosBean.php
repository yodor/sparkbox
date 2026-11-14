<?php
include_once("beans/DBTableBean.php");

class PhotosBean extends DBTableBean
{
    public function __construct()
    {
        parent::__construct("photos");
    }
}
