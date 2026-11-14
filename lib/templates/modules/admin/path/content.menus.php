<?php
include_once("beans/MenuItemsBean.php");
include_once("forms/MenuItemForm.php");

if (URL::Current()->contains("editID")) {
    //MenuItemForm needs the bean - set when the content is set up
    $config = TemplateConfig::Editor(MenuItemsBean::class, "");
    $config->title = tr("Menu Item");
    $config->observer = function (TemplateEvent $event) {
        if (!$event->isEvent(TemplateEvent::CONTENT_SETUP)) return;
        $content = $event->getSource();
        if (!($content instanceof BeanEditor)) throw new Exception("Incorrect event source - expecting BeanEditor");
        $bean = $content->getBean();
        if (!($bean instanceof NestedSetBean)) throw new Exception("MenuItemsBean expected");
        $content->setForm(new MenuItemForm($bean));
    };
}
else {
    $config = TemplateConfig::Tree(MenuItemsBean::class);
    $config->title = tr("Main Menu");
    //label of the tree items
    $config->listFields = array("menu_title" => "Menu Title");
    $config->clearNavigation = true;
}
