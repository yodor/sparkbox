<?php
include_once("utils/Session.php");
include_once("components/Action.php");

class Navigation
{
    protected array $urls = array();
    protected string $name;

    public function __construct(string $name="Navigation")
    {
        $this->name = $name;
        $this->urls = Session::Get($name, array());
    }

    public function entries() : array
    {
        return $this->urls;
    }

    public function push(string $pageName)
    {

        $pageURL = SparkPage::instance()->getURL();

        debug("Navigated to: ".SparkPage::instance()->getPageURL()." - Navigation contents: ".print_r($this->urls,true));

        $stored_urls = new ArrayIterator($this->urls, true);

        $urlbuild = new URLBuilder();

        $urls = array();

        if (count($this->urls)>0) {
            debug("Rebuilding navigation entries");
            while ($stored_urls->valid()) {

                $title = $stored_urls->key();
                $href = $stored_urls->current();

                $urlbuild->buildFrom($href);

                if (strcmp($urlbuild->getScriptName(), $pageURL->getScriptName()) == 0) {
                    debug("Current page url is already in the navigation: '$title' - Clearing remaining entries");
                    break;
                }

                //add back
                $urls[$title] = $urlbuild->url();

                $stored_urls->next();
            }
        }

        //navigation entries are constructed by using $pagename as unique key not the url
        $urls[$pageName] = $pageURL->url();

        $this->urls = $urls;

        Session::Set($this->name, $this->urls);
        debug("Adding page to navigation '$pageName' => {$pageURL->url()} - Naviagtion contents: ".print_r($this->urls,true));
    }

    public function clear()
    {
        debug("Before clear - Naviagtion contents: ".print_r($this->urls,true));
        $this->urls = array();
        Session::Set($this->name, $this->urls);
    }

    public function back() : ?Action
    {
        debug("Navigation entries: ".print_r($this->urls,true));

        if (count($this->urls)<1)return NULL;

        $reverted = new ArrayIterator(array_reverse($this->urls, true));

        $urlbuild = new URLBuilder();

        $action = NULL;

        while ($reverted->valid()) {

            $title = $reverted->key();
            $href = $reverted->current();

            $urlbuild->buildFrom($href);

            if (strcmp($urlbuild->getScriptName(), SparkPage::Instance()->getURL()->getScriptName())==0) {
                //skip the current entry added in push()
                $reverted->next();
            }
            else {
                $action = new Action($title, $href);
                debug("Using href: ".$href);
                //$action->getURLBuilder()->setKeepRequestParams(FALSE);
                break;
            }

        }
        return $action;

    }
}
