<?php
include_once("beans/FAQSectionsBean.php");
include_once("forms/FAQSectionInputForm.php");

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(FAQSectionsBean::class, FAQSectionInputForm::class);
}
else {
    $config = TemplateConfig::List(FAQSectionsBean::class);
    $config->title = tr("FAQ Sections");
    $config->listFields = array("section_name" => "Section");
}
