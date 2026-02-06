<?php

/**
 * Interface IRequestProcessor
 * Implemented from objects that handle Request/Get/Post variables
 */
interface IRequestProcessor
{
    public function processInput(): void;

    /**
     * Return true if request data has loaded into this processor
     * @return bool
     */
    public function isProcessed() : bool;
}

?>