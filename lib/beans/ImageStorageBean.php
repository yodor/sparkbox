<?php
include_once("lib/beans/DBTableBean.php");

class ImageStorageBean extends DBTableBean
{
    protected $createString = "CREATE TABLE `image_storage` (
 `isID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `photo` longblob NOT NULL,
 `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `caption` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '',
 PRIMARY KEY (`isID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("image_storage");
    }

}

?>