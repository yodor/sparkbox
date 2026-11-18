<?php
include_once("beans/LanguagesBean.php");
include_once("forms/LanguageForm.php");
include_once("templates/content/BeanEditor.php");
include_once("templates/content/BeanList.php");

if (URL::Current()->contains("editID")) {
    $config = BeanEditor::Configure(LanguagesBean::class, LanguageForm::class);
    $config->title = tr("Language");
}
else {
    $config = BeanList::Configure(LanguagesBean::class);
    $config->title = tr("Languages");
    $config->listFields = array("lang_code" => "Language Code", "language" => "Language");
    $config->clearNavigation = true;
}
