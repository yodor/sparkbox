<?php
include_once("templates/TemplateContent.php");
include_once("components/Publications.php");
include_once("utils/url/PublicationURL.php");
include_once("utils/script/LDJsonScript.php");
include_once("objects/data/LinkedData.php");
include_once("utils/Paginator.php");
include_once("beans/NewsItemsBean.php");

/**
 * Publications of a DatedBean (TemplateConfig beanClass - news: NewsItemsBean) at their module addresses:
 *  - home: the newest, paginated - the old ?<key>= / ?year=&month= addresses → 301 to their own address
 *  - view (?<key>=): one item - its own address <folder><id>/<slug>/, Article linked data
 *  - archive (?year=&month=): the items of a month - <folder>archive/<year>/<month>/
 * Markup: aside.column (latest, archive), main.column (the items) - styled by the layer or the site.
 */
class PublicationsContent extends TemplateContent
{
    /**
     * Module folder of the site (the publications module link) - item and archive addresses
     * @var string
     */
    public static string $Folder = "/news/";

    protected ?Publications $publications = null;

    public function initialize() : void
    {
        $bean = $this->bean;
        if (!($bean instanceof DatedBean)) throw new Exception("TemplateConfig beanClass must be a DatedBean");
        $key = $bean->key();
        $page = SparkPage::Instance();

        $itemURL = new PublicationURL(PublicationsContent::$Folder, $key);
        $archiveURL = new PublicationArchiveURL(PublicationsContent::$Folder);
        $mode = $this->config->filename;

        if (strcmp($mode, "view") === 0) {
            $row = $bean->getResult($key, (string)(int)($_GET[$key] ?? 0), "item_title", "item_date", "content");
            if (!is_array($row)) $this->moduleHome(tr("Publication not found"));
            $url = clone $itemURL;
            $url->setData(array($key => (int)$_GET[$key], "item_title" => (string)$row["item_title"]));
            Module::RedirectCanonical($url);
            $this->setPageURL($url, array($key, "item_title"));
            $this->config->title = (string)$row["item_title"];
            $page->setMetaDescription(Spark::MetaDescription((string)$row["content"], 150));
            $this->linkedData($url, $row, (int)$_GET[$key]);
        }
        else if (strcmp($mode, "archive") === 0) {
            $year = (int)($_GET["year"] ?? 0);
            $month = (int)($_GET["month"] ?? 0);
            if ($year < 1900 || $month < 1 || $month > 12) $this->moduleHome();
            $url = clone $archiveURL;
            $url->setData(array("year" => $year, "month" => $month));
            Module::RedirectCanonical($url);
            $this->setPageURL($url, array("year", "month", Paginator::KEY_PAGE));
            $this->config->title = $this->listTitle() . " - " . DatedBean::MonthName($month) . " " . $year;
        }
        else {
            //old addresses of the module itself
            if (isset($_GET[$key])) {
                $row = $bean->getResult($key, (string)(int)$_GET[$key], "item_title");
                if (!is_array($row)) $this->moduleHome(tr("Publication not found"));
                $url = clone $itemURL;
                $url->setData(array($key => (int)$_GET[$key], "item_title" => (string)$row["item_title"]));
                $this->redirect($url, 301);
            }
            if (isset($_GET["year"]) && isset($_GET["month"])) {
                $url = clone $archiveURL;
                $url->setData(array("year" => (int)$_GET["year"], "month" => (int)$_GET["month"]));
                $this->redirect($url, 301);
            }
            $page->head()->addCanonicalParameter(Paginator::KEY_PAGE);
            $this->config->title = $this->listTitle();
        }

        $this->publications = new Publications($bean, Module::PathURL(""), $itemURL, $archiveURL);
        $this->publications->processInput();

        $this->cmp = new Container(false);

        $aside = new Container(false);
        $aside->setTagName("aside");
        $aside->setComponentClass("column");
        $aside->items()->append($this->publications->getLatest());
        $aside->items()->append($this->publications->getArchive());
        $this->cmp->items()->append($aside);

        $main = new Container(false);
        $main->setTagName("main");
        $main->setComponentClass("column");
        $main->items()->append($this->publications->getMain());
        $this->cmp->items()->append($main);
    }

    /**
     * Title of the list - the configured title or "News"
     */
    protected function listTitle() : string
    {
        return tr("News");
    }

    protected function setPageURL(URL $url, array $canonicalParameters) : void
    {
        $page = SparkPage::Instance();
        if (method_exists($page, "setContentURL")) $page->setContentURL($url);
        $page->head()->addCanonicalParameter(...$canonicalParameters);
    }

    /**
     * Article linked data of the item
     */
    protected function linkedData(URL $url, array $row, int $id) : void
    {
        $data = new LinkedData("NewsArticle");
        $data->set("headline", mb_substr((string)$row["item_title"], 0, 110));
        $data->set("datePublished", (string)$row["item_date"]);
        $data->set("url", $url->fullURL()->toString());
        $image = StorageItem::Image($id, get_class($this->bean))->fullURL()->toString();
        $data->set("image", $image);
        SparkPage::Instance()->head()->addOGTag("image", $image);
        $script = new LDJsonScript();
        $script->setLinkedData($data);
        SparkPage::Instance()->head()->addScript($script);
    }

    protected function redirect(URL $url, int $code) : never
    {
        header("Location: " . $url->toString(), true, $code);
        exit;
    }

    protected function moduleHome(string $alert = "") : never
    {
        if ($alert !== "") Session::SetAlert($alert);
        header("Location: " . Spark::Get(Config::LOCAL) . PublicationsContent::$Folder);
        exit;
    }

    public function component() : Component
    {
        return $this->cmp;
    }

    protected function getContentTitle() : string
    {
        return $this->listTitle();
    }
}
