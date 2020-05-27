<?php

/**
 * Interface IRequestProcessor
 * Object that handle Request/Get/Post
 */
interface IRequestProcessor
{
    public function processInput();

    /**
     * Return true if request data has loaded into this processor
     * @return bool
     */
    public function isProcessed() : bool;
}

?>