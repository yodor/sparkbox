<?php
include_once("beans/FAQItemsBean.php");
include_once("forms/FAQItemInputForm.php");

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(FAQItemsBean::class, FAQItemInputForm::class);
}
else {
    $bean = new FAQItemsBean();
    $query = $bean->query("fi.fID", "fs.section_name", "fi.question", "fi.answer");
    $query->stmt->from()->append(" fi ")->leftJoin("faq_sections fs")->on("fs.fqsID = fi.fqsID");

    $config = TemplateConfig::List(FAQItemsBean::class);
    $config->title = tr("FAQ");
    $config->iterator = $query;
    $config->listFields = array("section_name" => "Section", "question" => "Question", "answer" => "Answer");
    $config->clearNavigation = true;
}
