<?php
include_once("templates/TemplateContent.php");
include_once("beans/DynamicPagesBean.php");
include_once("utils/url/DynamicPageURL.php");

/**
 * The module address itself (/pages/, /pages/index.php):
 *  - ?id= / ?page_id= / ?dpID= (old links, menu items): 301 to the page address
 *  - ?page_class= / ?class= (a group - 'footer_page;terms'): the newest visible page of the group (302 - the newest
 *    page can change)
 *  - otherwise the site home
 */
class DynamicPageLink extends TemplateContent
{
    public function initialize() : void
    {
        $pages = new DynamicPagesBean();

        $dpID = (int)($_GET["dpID"] ?? $_GET["id"] ?? $_GET["page_id"] ?? 0);
        if ($dpID > 0) {
            $row = $pages->getResult($pages->key(), (string)$dpID, "item_title", "visible");
            if (is_array($row) && (int)$row["visible"] > 0) {
                header("Location: " . DynamicPageURL::Page($dpID, (string)$row["item_title"])->toString(), true, 301);
                exit;
            }
            $this->home(tr("Page not found"));
        }

        $levels = DynamicPagesBean::Levels((string)($_GET["page_class"] ?? $_GET["class"] ?? ""));
        if (count($levels) > 0) {
            $query = $pages->groupQuery($levels, false, "item_title");
            $query->stmt->limit(1);
            $query->exec();
            $row = $query->nextResult();
            $query->free();
            if ($row instanceof RawResult) {
                header("Location: " . DynamicPageURL::Page((int)$row->get($pages->key()), (string)$row->get("item_title"))->toString(), true, 302);
                exit;
            }
            $this->home(tr("Page not found"));
        }

        $this->home();
    }

    protected function home(string $alert = "") : never
    {
        if ($alert !== "") Session::SetAlert($alert);
        header("Location: " . Spark::Get(Config::LOCAL) . "/");
        exit;
    }

    protected function getContentTitle() : string
    {
        return "Page";
    }
}
