<?php
include_once("beans/DynamicPagesBean.php");
include_once("beans/DynamicPagePhotosBean.php");
include_once("forms/PhotoForm.php");
include_once("templates/Template.php");
include_once("utils/BeanKeyCondition.php");
include_once("templates/content/BeanEditor.php");
include_once("templates/content/BeanGallery.php");

Template::Condition(new BeanKeyCondition(new DynamicPagesBean(), Module::PathURL("/content/pages"), array("item_title")));

if (URL::Current()->contains("editID")) {
    $config = BeanEditor::Configure(DynamicPagePhotosBean::class, PhotoForm::class);
    $config->title = tr("Image") . ": " . Template::Condition()->getData("item_title");
}
else {
    $config = BeanGallery::Configure(DynamicPagePhotosBean::class);
    $config->title = tr("Photo Gallery") . ": " . Template::Condition()->getData("item_title");
}
$config->useCondition = true;
