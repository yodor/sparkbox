<?php
include_once("templates/content/SiteTextsList.php");

$config = TemplateConfig::Factory();
$config->contentClass = SiteTextsList::class;
$config->title = tr("Translator");
