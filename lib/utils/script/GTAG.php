<?php
include_once("components/Script.php");

class GTAG extends Script
{
    //default gtag without gtmID, later on configured using GTMCommand 'config'
    public function __construct(string $gtmID="")
    {
        parent::__construct();

        $this->setAttribute("async");
        $this->setAttribute("fetchpriority","low");

        $this->setSrc("https://www.googletagmanager.com/gtag/js");
        if ($gtmID) {
            $this->srcURL->add(new URLParameter("id", $gtmID));
        }
    }
}