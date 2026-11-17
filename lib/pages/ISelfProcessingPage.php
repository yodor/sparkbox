<?php

/**
 * A page that answers its module path itself, without a module content (the product pages): the module engine
 * creates it, calls initialize() and processInput(), then the request step and render(). Every other page is a
 * frame - the engine puts the content of the path into it (SparkPage::setContent).
 */
interface ISelfProcessingPage
{
    public function processInput() : void;
}
