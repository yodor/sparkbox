<?php
include_once("components/Container.php");

class HTMLBody extends Container
{
    public function __construct()
    {
        parent::__construct();
        $this->setTagName("body");
    }
}
?>
