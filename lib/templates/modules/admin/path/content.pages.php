<?php
include_once("beans/DynamicPagesBean.php");
include_once("forms/DynamicPageForm.php");
include_once("components/renderers/cells/BooleanCell.php");
include_once("components/renderers/cells/ImageCell.php");
include_once("components/renderers/cells/DateCell.php");

if (URL::Current()->contains("editID")) {
    $config = TemplateConfig::Editor(DynamicPagesBean::class, DynamicPageForm::class);
}
else {
    $config = TemplateConfig::List(DynamicPagesBean::class);
    $config->title = tr("Dynamic Pages");
    $config->listFields = array("position" => "#", "item_photo" => "Photo", "item_title" => "Title",
        "item_date" => "Date", "visible" => "Visible", "keywords" => "Keywords");
    $config->searchField = array("item_title", "keywords");
    $config->clearNavigation = true;

    $config->observer = function (TemplateEvent $event) {
        if (!$event->isEvent(TemplateEvent::CONTENT_INITIALIZED)) return;
        $content = $event->getSource();
        if (!($content instanceof BeanList)) throw new Exception("Incorrect event source - expecting BeanList");

        $view = $content->tableView();
        $view->getColumn("item_photo")->setCellRenderer(new ImageCell());
        $view->getColumn("visible")->setCellRenderer(new BooleanCell("Yes", "No"));
        $view->getColumn("item_date")->setCellRenderer(new DateCell());

        $actions = $content->getItemActions()->getActions();
        $actions->append(Action::RowSeparator());
        $gallery = TemplateContent::CreateAction("Photo Gallery", tr("Photo Gallery"), "gallery/");
        $gallery->getURL()->add(new DataParameter($content->getBean()->key()));
        $actions->append($gallery);
    };
}
