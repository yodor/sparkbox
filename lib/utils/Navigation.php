<?php
include_once("utils/Session.php");
include_once("components/Action.php");
include_once("responders/RequestController.php");

class Navigation
{

    protected string $name;
    protected SessionData $urldata;

    public function __construct(string $name="Navigation")
    {
        $this->name = $name;
        $this->urldata = new SessionData($this->name);
    }

    /**
     * Push current url to navigation using pageName as access key
     * @param string $pageName
     * @return void
     * @throws Exception
     */
    public function push(string $pageName) : void
    {

        //current URL
        $pageURL = URL::Current();

        debug("Pushing $pageName - Current URL: ".$pageURL);

        if ($this->urldata->count()>0) {
            //check if is already present and splice
            debug("Rebuilding navigation entries");

            $clearRemaining = false;

            $pages = $this->urldata->keys();
            foreach ($pages as $page) {
                if ($clearRemaining) {
                    $this->urldata->remove($page);
                }
                else {
                    //get the stored url
                    $storedURL = $this->urldata->get($page);

                    if ($storedURL == $pageURL) {
                        debug("Current page url is already in the navigation as '$page' - Clearing remaining entries");
                        $clearRemaining = true;
                    }
                }
            }

        }

        debug("Adding page to navigation '$pageName' => $pageURL");
        //navigation entries are constructed by using $pagename as unique key not the url
        $this->urldata->set($pageName, $pageURL);

    }

    public function clear() : void
    {
        if (RequestController::isJSONRequest()) {
            debug("Not clearing for JSONRequest");
        }
        else {
            debug("Clearing all navigation entries");
            $this->urldata->removeAll();

        }
    }

    public function back() : ?Action
    {
        debug("Requested back action");

        if ($this->urldata->count()<1)return NULL;

        $pages = array_reverse($this->urldata->keys(), true);

        $action = NULL;

        $current_script = URL::Current()->getScriptName();

        foreach ($pages as $pageName) {
            $storedURL = $this->urldata->get($pageName);
            if (strcmp($storedURL->getScriptName(), $current_script)==0) {
                continue;
            }
            else {
                $action = new Action($pageName, $storedURL->toString());
                debug("Using href: ".$action->getURL());
                break;
            }
        }

        return $action;

    }
}
