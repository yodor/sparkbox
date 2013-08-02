<?php

//tag to recognize all components that need to be renderer before ending body tag
//All implementing IFinalRenderer delegate rendering to SimplePage
interface IFinalRenderer
{
    public function renderFinal();

}

?>