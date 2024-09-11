<?php
include_once("utils/Session.php");
include_once("components/Action.php");
include_once("responders/RequestController.php");

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

    /**
     * Push current page URL to the stack using key $pageName
     * @param string $pageName
     * @return void
     */
    public function push(string $pageName) : void
    {

        //current URL
        $pageURL = SparkPage::instance()->getURL();

        debug("Navigated to: ".SparkPage::instance()->getPageURL()." - Navigation contents: ".print_r($this->urls,true));

        $stored_urls = new ArrayIterator($this->urls, true);

        $urlbuild = new URL();

        $urls = array();

        if (count($this->urls)>0) {
            debug("Rebuilding navigation entries");
            while ($stored_urls->valid()) {

                $title = $stored_urls->key();
                $href = $stored_urls->current();

                //same page ?
                $urlbuild->fromString($href);

                if ($urlbuild == $pageURL) {
                    debug("Current page url is already in the navigation: '$title' - Clearing remaining entries");
                    break;
                }

                //add back
                $urls[$title] = $urlbuild->toString();

                $stored_urls->next();
            }
        }

        //navigation entries are constructed by using $pagename as unique key not the url
        $urls[$pageName] = $pageURL->toString();

        $this->urls = $urls;

        Session::Set($this->name, $this->urls);
        debug("Adding page to navigation '$pageName' => {$pageURL->toString()} - Naviagtion contents: ".print_r($this->urls,true));
    }

    public function clear()
    {
        if (RequestController::isJSONRequest()) {
            debug("Not clearing for JSONRequest");
        }
        else {
            debug("Before clear - Naviagtion contents: " . print_r($this->urls, true));
            $this->urls = array();
            Session::Set($this->name, $this->urls);
        }
    }

    public function back() : ?Action
    {
        debug("Navigation entries: ".print_r($this->urls,true));

        if (count($this->urls)<1)return NULL;

        $reverted = new ArrayIterator(array_reverse($this->urls, true));

        $urlbuild = new URL();

        $action = NULL;

        while ($reverted->valid()) {

            $title = $reverted->key();
            $href = $reverted->current();

            $urlbuild->fromString($href);

            if (strcmp($urlbuild->getScriptName(), SparkPage::Instance()->getURL()->getScriptName())==0) {
                //skip the current entry added in push()
                $reverted->next();
            }
            else {
                $action = new Action($title, $href);
                debug("Using href: ".$href);
                break;
            }

        }
        return $action;

    }
}
