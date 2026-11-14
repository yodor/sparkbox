<?php
include_once("beans/LanguagesBean.php");
include_once("templates/content/PhraseTranslations.php");

Template::Condition(new BeanKeyCondition(new LanguagesBean(), Module::PathURL("/settings/languages/translator"), array("language")));

$config = TemplateConfig::Factory();
$config->contentClass = PhraseTranslations::class;
$config->title = tr("Translations For") . ": " . Template::Condition()->getData("language");
