<?php
include_once("objects/IObserver.php");
include_once("objects/SparkEvent.php");

interface IObservable {

    public function setObserver(IObserver $observer);
    public function getObserver() : ?IObserver;
    public function notify(SparkEvent $event);

};

?>