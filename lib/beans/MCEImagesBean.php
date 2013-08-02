<?php
include_once("lib/beans/DBTableBean.php");

class MCEImagesBean extends DBTableBean
{
	protected $createString = "CREATE TABLE `mce_images` (
 `imageID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `section` varchar(255) NOT NULL,
 `key` varchar(255) DEFAULT NULL,
 `ownerID` int(11) DEFAULT NULL,
 `photo` longblob NOT NULL,
 `auth_context` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`imageID`),
 UNIQUE KEY `section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

	public function __construct()
	{
		parent::__construct("mce_images");
	}
	
}

?>