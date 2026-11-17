<?php

/**
 * Implemented by objects that handle the request variables (Request / Get / Post): forms, filters, contents -
 * and the pages that answer their module path themselves (the product pages): the module engine calls their
 * processInput() instead of loading a content into them.
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