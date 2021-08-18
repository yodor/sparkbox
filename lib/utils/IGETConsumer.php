<?php

/**
 * Interface implemented from classes that access the GET request parameters
 */
interface IGETConsumer
{
    /**
     * @return array The parameter names this object is interacting with
     */
    public function getParameterNames() : array;

}

?>