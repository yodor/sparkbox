<?php

//tag to recognize all components that need to be renderer before ending body tag
interface IHeadRenderer
{
    public function renderStyle();
    public function renderScript();
    public function getHeadClass();
}

?>