<?php
include_once("beans/DynamicPagesBean.php");
include_once("forms/DynamicPageForm.php");
include_once("components/renderers/cells/BooleanCell.php");
include_once("components/renderers/cells/ImageCell.php");
include_once("components/renderers/cells/DateCell.php");
include_once("utils/url/DynamicPageURL.php");

/**
 * Chooser mode (?chooser=<base64 of an admin address> - the menu item editor): each page gets 'Choose Page', back to
 * that address with page_id. Only addresses of this admin module are accepted.
 */
function DynamicPagesChooserURL() : ?URL
{
    $encoded = (string)($_GET["chooser"] ?? "");
    if ($encoded === "") return null;
    $target = (string)base64_decode($encoded, true);
    $url = new URL($target);
    $location = Module::Active()->getLocation();
    if ($target === "" || $url->isAbsolute() || !str_starts_with($url->getScriptName(), $location)) {
        Debug::ErrorLog("Chooser address refused: $target");
        return null;
    }
    return $url;
}

if (URL::Current()->contains("editID")) {
    $config = BeanEditor::Configure(DynamicPagesBean::class, DynamicPageForm::class);
}
else {
    $config = BeanList::Configure(DynamicPagesBean::class);
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

        //the page on the site - to tell the pages apart
        $preview = new Action("Preview");
        $preview->setContents(tr("Preview"));
        $preview->setURL(new DynamicPageURL());
        $preview->setAttribute("target", "_blank");
        $actions->append(Action::RowSeparator());
        $actions->append($preview);

        $chooser = DynamicPagesChooserURL();
        if ($chooser instanceof URL) {
            $choose = new Action("Choose Page");
            $choose->setContents(tr("Choose Page"));
            $chooseURL = clone $chooser;
            $chooseURL->remove("page_id");
            $chooseURL->add(new DataParameter("page_id", $content->getBean()->key()));
            $choose->setURL($chooseURL);
            $actions->append(Action::RowSeparator());
            $actions->append($choose);
        }

        $actions->append(Action::RowSeparator());
        $gallery = TemplateBeanContent::CreateAction("Photo Gallery", tr("Photo Gallery"), "gallery/");
        $gallery->getURL()->add(new DataParameter($content->getBean()->key()));
        $actions->append($gallery);
    };
}
