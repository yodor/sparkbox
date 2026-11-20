<?php
include_once("utils/url/URL.php");
include_once("utils/url/PathParameter.php");

/**
 * Address of a publication (DatedBean - news): <LOCAL><folder><id>/<title slug>/ - data keys: the bean key,
 * item_title. The news module answers it as its 'view' path (addPattern in its init.php).
 */
class PublicationURL extends URL
{
    public function __construct(string $folder = "/news/", string $key = "newsID")
    {
        parent::__construct();
        $this->fromString(Spark::Get(Config::LOCAL) . $folder);
        $this->add(new PathParameter($key, $key, false));
        $this->add(new PathParameter("item_title", "item_title", true));
    }
}

/**
 * Archive month of publications: <LOCAL><folder>archive/<year>/<month>/ - data keys: year, month
 */
class PublicationArchiveURL extends URL
{
    public function __construct(string $folder = "/news/")
    {
        parent::__construct();
        $this->fromString(Spark::Get(Config::LOCAL) . $folder . "archive/");
        $this->add(new PathParameter("year", "year", false));
        $this->add(new PathParameter("month", "month", false));
    }
}
