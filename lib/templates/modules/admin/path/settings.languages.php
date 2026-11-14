<?php
include_once("beans/LanguagesBean.php");
include_once("forms/LanguageForm.php");

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(LanguagesBean::class, LanguageForm::class);
    $config->title = tr("Language");
}
else {
    $config = TemplateConfig::List(LanguagesBean::class);
    $config->title = tr("Languages");
    $config->listFields = array("lang_code" => "Language Code", "language" => "Language");
    $config->clearNavigation = true;
}
