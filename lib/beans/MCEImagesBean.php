<?php
include_once("beans/DBTableBean.php");

class MCEImagesBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `mce_images` (
 `imageID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `section` varchar(255) NOT NULL,
 `section_key` varchar(255) NOT NULL,
 `ownerID` int(11) DEFAULT NULL,
 `photo` longblob NOT NULL,
 `auth_context` varchar(255) DEFAULT NULL,
 `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (`imageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("mce_images");
    }

}

?>
