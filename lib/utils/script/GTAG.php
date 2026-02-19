<?php
include_once("components/Script.php");

class GTAG extends Script
{
    public function __construct()
    {
        parent::__construct();

        $this->setAttribute("async");
        $this->setAttribute("fetchpriority","low");
        $this->setSrc("https://www.googletagmanager.com/gtag/js");
    }
}