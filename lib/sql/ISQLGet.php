<?php

interface ISQLGet {

    /**
     * Returns prepared SQL text for this statement
     * @return string
     */
    public function getSQL() : string;

}