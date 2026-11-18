<?php
//a page: /pages/<dpID>/<slug>/
include_once("templates/TemplateConfig.php");
include_once("templates/modules/pages/content/DynamicPageContent.php");
$config = TemplateConfig::Factory();
$config->contentClass = DynamicPageContent::class;
$config->requireAuth = false;
