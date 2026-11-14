<?php

/**
 * Source of a version token for URLs (URL::setVersion()): the token changes when the resource changes,
 * so the versioned URL can be cached for a long time.
 */
interface IVersion
{
    /**
     * @return string Token of the current version - empty string if no version is known
     */
    public function token() : string;
}
