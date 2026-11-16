<?php
include_once("utils/url/URL.php");
include_once("utils/url/PathParameter.php");

/**
 * Address of a dynamic page (DynamicPagesBean): <LOCAL>/pages/<dpID>/<title slug>/ - the pages module.
 * Data keys: dpID, item_title (a row of the bean). Group(): the newest page of a page group (keywords levels).
 */
class DynamicPageURL extends URL
{
    /**
     * The pages module folder of the site
     */
    public static string $urlPageSlug = "/pages/";

    public function __construct(?URL $other = null)
    {
        parent::__construct();

        $this->fromString(Spark::Get(Config::LOCAL) . self::$urlPageSlug);
        $this->add(new PathParameter("dpID", "dpID", false));
        $this->add(new PathParameter("item_title", "item_title", true));

        if (!is_null($other)) {
            $this->copyParametersFrom($other, false);
        }
    }

    /**
     * URL of the page '$dpID' titled '$title'
     */
    public static function Page(int $dpID, string $title) : DynamicPageURL
    {
        $url = new DynamicPageURL();
        $url->setData(array("dpID" => $dpID, "item_title" => $title));
        return $url;
    }

    /**
     * URL of the newest page of the group '$levels' (keywords levels, ';' separated - 'footer_page;terms')
     */
    public static function Group(string $levels) : URL
    {
        $url = new URL(Spark::Get(Config::LOCAL) . self::$urlPageSlug);
        $url->add(new URLParameter("page_class", $levels));
        return $url;
    }
}
