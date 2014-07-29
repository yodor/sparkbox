<?php
include_once ("lib/beans/OrderedDataBean.php");


class GalleryPhotosBean extends  OrderedDataBean
{

    protected $createString = "CREATE TABLE `gallery_photos` (
 `gpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 `mime` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
 `size` int(11) NOT NULL,
 `photo` longblob NOT NULL,
 `width` int(11) NOT NULL,
 `height` int(11) NOT NULL,
 `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `type` int(11) DEFAULT NULL,
 `caption` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
 `position` int(11) NOT NULL,
 PRIMARY KEY (`gpID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    public function __construct() 
    {
	parent::__construct("gallery_photos");
    }

}

?>