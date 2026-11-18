<?php
include_once("beans/FAQSectionsBean.php");
include_once("forms/FAQSectionInputForm.php");
include_once("templates/content/BeanEditor.php");
include_once("templates/content/BeanList.php");

if (URL::Current()->contains("editID")) {
    $config = BeanEditor::Configure(FAQSectionsBean::class, FAQSectionInputForm::class);
}
else {
    $config = BeanList::Configure(FAQSectionsBean::class);
    $config->title = tr("FAQ Sections");
    $config->listFields = array("section_name" => "Section");
}
