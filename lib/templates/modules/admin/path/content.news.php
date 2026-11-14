<?php
include_once("beans/NewsItemsBean.php");
include_once("forms/NewsItemInputForm.php");
include_once("components/renderers/cells/ImageCell.php");
include_once("components/renderers/cells/DateCell.php");

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(NewsItemsBean::class, NewsItemInputForm::class);
}
else {
    $config = TemplateConfig::List(NewsItemsBean::class);
    $config->title = tr("News Items");
    $config->listFields = array("item_photo" => "Photo", "item_title" => "Title", "item_date" => "Date");
    $config->searchField = array("item_title");
    $config->clearNavigation = true;

    $config->observer = function (TemplateEvent $event) {
        if (!$event->isEvent(TemplateEvent::CONTENT_INITIALIZED)) return;
        $content = $event->getSource();
        if (!($content instanceof BeanList)) throw new Exception("Incorrect event source - expecting BeanList");
        $content->tableView()->getColumn("item_photo")->setCellRenderer(new ImageCell());
        $content->tableView()->getColumn("item_date")->setCellRenderer(new DateCell());
    };
}
