<?php
include_once("components/Script.php");

class GTAG extends Script
{
    protected string $id = "";

    protected Script $gtag;
    protected Script $script;

    public function __construct()
    {
        parent::__construct();
        $this->gtag = new Script();
        $this->gtag->setAttribute("async");
        $this->gtag->setAttribute("fetchpriority","low");
        $this->script = new Script();
        $this->items()->append($this->gtag);
        $this->items()->append($this->script);
        //disable wrapper tag
        $this->wrapper_enabled = false;
    }

    public function getID() : string
    {
        return $this->id;
    }

    public function setID(string $id) : void
    {
        $this->id = $id;
        $this->gtag->setSrc("https://www.googletagmanager.com/gtag/js?id={$this->id}");

        $contents = <<<JS
            //Start GTAG script for ID: {$this->id}
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', '{$this->id}');

            gtag('consent', 'default', {
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'ad_storage': 'granted',
                'analytics_storage': 'granted',
                'wait_for_update': 5000
            });
            //End GTAG script for ID: {$this->id}
JS;
        $this->script->setContents($contents);
    }

}

?>