<?php
include_once("templates/TemplateContent.php");
include_once("beans/DynamicPagesBean.php");
include_once("utils/url/DynamicPageURL.php");
include_once("storage/StorageItem.php");
include_once("pages/SparkPage.php");
include_once("templates/Module.php");

/**
 * A dynamic page (?dpID= - /pages/<dpID>/<slug>/): its title, photo and content, and the local menu of its group.
 *
 * The group of a page: its keywords levels without the last ('footer_page;terms;' → footer_page). The menu lists
 * the visible pages of the group (admin position, then the newest) when there is more than one.
 * Markup: 'column side page_menu <levels>' > 'menu_links' > a.item, and 'column page_data <levels>' > Caption,
 * page_photo, summary.
 */
class DynamicPageContent extends TemplateContent
{
    protected ?RawResult $result = null;

    protected ?DynamicPagesBean $pages = null;

    public function initialize() : void
    {
        $this->pages = new DynamicPagesBean();
        $dpID = (int)($_GET["dpID"] ?? 0);

        $query = $this->pages->query($this->pages->key(), "item_title", "content", "keywords", "item_date");
        $query->stmt->alias("(photo IS NOT NULL)", "have_photo");
        $query->stmt->where()->match($this->pages->key(), $dpID);
        $query->stmt->where()->match("visible", 1);
        $query->stmt->limit(1);
        $query->exec();
        $this->result = $query->nextResult();
        $query->free();

        if (!($this->result instanceof RawResult)) {
            Session::SetAlert(tr("Page not found"));
            header("Location: " . Spark::Get(Config::LOCAL) . "/");
            exit;
        }

        $title = (string)$this->result->get("item_title");
        $url = DynamicPageURL::Page($dpID, $title);

        //one address per page: an old slug or the script address → 301
        $page = SparkPage::Instance();
        Module::RedirectCanonical($url);
        $page?->setContentURL($url);
        //the canonical link - the page address (currentURL)
        $page?->head()->addCanonicalParameter("dpID", "item_title");

        $this->config->title = $title;
        $page?->setMetaDescription(Spark::MetaDescription((string)$this->result->get("content"), 150));

        $levels = DynamicPagesBean::Levels((string)$this->result->get("keywords"));
        $css = Spark::AttributeValue(implode(" ", $levels));

        $this->cmp = new Container(false);

        $menu = $this->createMenu($levels, $dpID);
        if ($menu instanceof Container) {
            $menu->addClassName($css);
            $this->cmp->items()->append($menu);
        }

        $column = new Container(false);
        $column->setComponentClass("column page_data");
        if ($css !== "") $column->addClassName($css);

        $caption = new Component(false);
        $caption->setComponentClass("Caption");
        $caption->setText($title);
        $column->items()->append($caption);

        if ($this->result->get("have_photo")) {
            $photo = new StorageItem($dpID, get_class($this->pages));
            $image = new Container(false);
            $image->setComponentClass("page_photo");
            $img = new Component(false);
            $img->setTagName("img");
            $img->setAttribute("src", $photo->hrefImage()->toString());
            $img->setAttribute("alt", $title);
            $image->items()->append($img);
            $column->items()->append($image);
            $page?->head()->addOGTag("image", $photo->hrefImage()->fullURL()->toString());
        }

        $summary = new Component(false);
        $summary->setComponentClass("summary");
        //the page content is the HTML of the admin editor
        $summary->setContents((string)$this->result->get("content"));
        $column->items()->append($summary);

        $this->cmp->items()->append($column);
    }

    /**
     * The local menu of the page group - null without a group or with only this page in it
     * @param array<string> $levels
     * @param int $dpID
     * @return Container|null
     */
    protected function createMenu(array $levels, int $dpID) : ?Container
    {
        array_pop($levels);
        if (count($levels) < 1) return null;

        $query = $this->pages->groupQuery($levels, true, "item_title");
        $query->stmt->alias("(photo IS NOT NULL)", "have_photo");
        $query->exec();

        $links = new Container(false);
        $links->setComponentClass("menu_links");
        $count = 0;
        while ($row = $query->nextResult()) {
            $itemID = (int)$row->get($this->pages->key());
            $itemTitle = (string)$row->get("item_title");

            $item = new Container(false);
            $item->setTagName("a");
            $item->setComponentClass("item");
            $item->setAttribute("href", DynamicPageURL::Page($itemID, $itemTitle)->toString());
            $item->setAttribute("title", $itemTitle);
            if ($itemID == $dpID) {
                $item->addClassName("selected");
                $item->setAttribute("aria-current", "page");
            }

            if ($row->get("have_photo")) {
                $photo = new Container(false);
                $photo->setComponentClass("photo");
                $img = new Component(false);
                $img->setTagName("img");
                $img->setAttribute("src", StorageItem::Image($itemID, get_class($this->pages))->toString());
                $img->setAttribute("alt", $itemTitle);
                $photo->items()->append($img);
                $item->items()->append($photo);
            }

            $label = new Component(false);
            $label->setTagName("span");
            $label->setText($itemTitle);
            $item->items()->append($label);

            $links->items()->append($item);
            $count++;
        }
        $query->free();
        if ($count < 2) return null;

        $menu = new Container(false);
        $menu->setComponentClass("column side page_menu");
        $menu->items()->append($links);
        return $menu;
    }

    public function component() : Component
    {
        return $this->cmp;
    }

    protected function getContentTitle() : string
    {
        return "Page";
    }
}
