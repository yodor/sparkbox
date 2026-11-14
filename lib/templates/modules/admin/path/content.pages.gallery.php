<?php
include_once("beans/DynamicPagesBean.php");
include_once("beans/DynamicPagePhotosBean.php");
include_once("forms/PhotoForm.php");

Template::Condition(new BeanKeyCondition(new DynamicPagesBean(), Module::PathURL("/content/pages"), array("item_title")));

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(DynamicPagePhotosBean::class, PhotoForm::class);
    $config->title = tr("Image") . ": " . Template::Condition()->getData("item_title");
}
else {
    $config = TemplateConfig::Gallery(DynamicPagePhotosBean::class);
    $config->title = tr("Photo Gallery") . ": " . Template::Condition()->getData("item_title");
}
$config->useCondition = true;
