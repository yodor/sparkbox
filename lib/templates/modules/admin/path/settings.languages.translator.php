<?php
include_once("templates/modules/admin/content/SiteTextsList.php");

$config = TemplateConfig::Factory();
$config->contentClass = SiteTextsList::class;
$config->title = tr("Translator");
