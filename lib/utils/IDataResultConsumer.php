<?php
include_once("utils/IDataResultProcessor.php");

interface IDataResultConsumer extends IDataResultProcessor {

    public function collectDataKeys() : array;

}