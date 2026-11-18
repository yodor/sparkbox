<?php
//the module address with ?id= (old links, the menu) or ?page_class= (a group: its newest page)
include_once("templates/TemplateConfig.php");
include_once("templates/modules/pages/content/DynamicPageLink.php");
$config = TemplateConfig::Factory();
$config->contentClass = DynamicPageLink::class;
$config->requireAuth = false;
