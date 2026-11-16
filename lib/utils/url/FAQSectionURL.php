<?php
include_once("utils/url/URL.php");
include_once("utils/url/PathParameter.php");

/**
 * Address of a FAQ section: <LOCAL>/faq/<fqsID>/<section name slug>/ - data keys: fqsID, section_name.
 * The faq module rewrites it to its 'section' path.
 */
class FAQSectionURL extends URL
{
    public static string $urlFolder = "/faq/";

    public function __construct()
    {
        parent::__construct();
        $this->fromString(Spark::Get(Config::LOCAL) . self::$urlFolder);
        $this->add(new PathParameter("fqsID", "fqsID", false));
        $this->add(new PathParameter("section_name", "section_name", true));
    }

    public static function Section(int $fqsID, string $name) : FAQSectionURL
    {
        $url = new FAQSectionURL();
        $url->setData(array("fqsID" => $fqsID, "section_name" => $name));
        return $url;
    }
}
