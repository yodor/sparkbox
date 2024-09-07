<?php
include_once("objects/SparkEvent.php");

interface IObserver {

    public function onEvent(SparkEvent $event) : void;

}

?>
