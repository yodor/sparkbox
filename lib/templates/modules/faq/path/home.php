<?php
include_once("templates/TemplateConfig.php");
include_once("templates/modules/faq/content/FAQContent.php");
$config = TemplateConfig::Factory();
$config->contentClass = FAQContent::class;
$config->requireAuth = false;
