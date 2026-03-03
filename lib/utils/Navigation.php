<?php
include_once("utils/Session.php");
include_once("components/Action.php");
include_once("responders/RequestController.php");

class Navigation
{

    protected string $name;
    protected SessionData $urldata;

    protected array $keys;

    public function __construct(string $name="Navigation")
    {
        $this->name = $name;
        $this->urldata = new SessionData($this->name);
        $this->keys = $this->urldata->keys();
    }

    /**
     * Push current url to navigation using pageName as access key
     * @param string $pageName
     * @return void
     * @throws Exception
     */
    public function push(string $pageName, ?URL $pageURL = null) : void
    {

        //current URL
        if (is_null($pageURL)) {
            $pageURL = URL::Current();
        }

        if (RequestController::isJSONRequest()) {
            Debug::ErrorLog("Not pushing JSONRequest ...");
            return;
        }
        if (RequestController::isResponderRequest()) {
            Debug::ErrorLog("Not pushing RequestResponder ...");
            return;
        }

        Debug::ErrorLog("Push [$pageName] - URL: ".$pageURL);

        if ($this->urldata->count()>0) {
            //check if is already present and slice
            //Debug::ErrorLog("Rebuilding navigation entries");

            $clearRemaining = false;

            //page names
            foreach ($this->keys as $page) {
                if ($clearRemaining) {
                    $this->urldata->remove($page);
                }
                else {
                    //get the stored url
                    $storedURL = $this->urldata->get($page);

                    //loose comparison
                    if (strcmp($storedURL->toString(),$pageURL->toString())===0) {
                        Debug::ErrorLog("URL already exists with page [$page] - Clearing remaining entries");
                        $clearRemaining = true;
                    }
                }
            }
        }

        $this->urldata->set($pageName, $pageURL);

        $this->keys = $this->urldata->keys();

        //debug
        $urls = array();
        foreach ($this->keys as $key) {
            $urls[$key] = $this->urldata->get($key);
        }
        Debug::ErrorLog("Current navigation: ", $urls);

    }

    public function clear() : void
    {
        if (RequestController::isJSONRequest()) {
            Debug::ErrorLog("Not clearing for JSONRequest");
        }
        else if (RequestController::isResponderRequest()) {
            Debug::ErrorLog("Not clearing for RequestResponder");
        }
        else {
            Debug::ErrorLog("Clearing all navigation entries");
            $this->urldata->removeAll();
            $this->keys = $this->urldata->keys();
        }
    }

    public function end() : void
    {
        end($this->keys);
    }
    public function prev() : void
    {
        prev($this->keys);
    }
    public function next() : void
    {
        next($this->keys);
    }
    public function reset() : void
    {
        reset($this->keys);
    }

    public function current() : ?URL
    {
        $key = current($this->keys);
        if ($key) {
            $url = $this->urldata->get($key);
            Debug::ErrorLog("Returning Entry [$key] - URL: ".$url);
            return $url;
        }
        return null;
    }

    public function back() : ?Action
    {
        Debug::ErrorLog("Requested back action");

        if ($this->urldata->count()==0){
            Debug::ErrorLog("URL backward history is empty");
            return NULL;
        }

        $pages = array_reverse($this->urldata->keys(), true);

        $action = NULL;

        $current_script = URL::Current()->getScriptName();

        foreach ($pages as $pageName) {
            $storedURL = $this->urldata->get($pageName);
            if (strcmp($storedURL->getScriptName(), $current_script)===0) {
                continue;
            }
            else {
                $action = new Action($pageName, $storedURL->toString());
                Debug::ErrorLog("Using URL: ".$action->getURL());
                break;
            }
        }

        return $action;

    }
}