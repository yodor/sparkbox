<?php

//tag to recognize all components that require rendering in the HEAD section of the page
interface IHeadRenderer
{
    public function renderStyle();
    public function renderScript();
    
    //used to preven duplicate rendering of CSS styles
    //SimplePage group components with same head class
    public function getHeadClass();
    
}

?>
