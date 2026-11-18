<?php
include_once("templates/BeanConfig.php");
include_once("templates/modules/news/content/PublicationsContent.php");
$config = BeanConfig::Factory();
$config->contentClass = PublicationsContent::class;
$config->beanClass = NewsItemsBean::class;
$config->requireAuth = false;
