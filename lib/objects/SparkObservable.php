<?php
include_once ("objects/SparkObject.php");
include_once ("objects/IObservable.php");
include_once ("objects/IObserver.php");

class SparkObservable extends SparkObject implements IObservable
{
    /**
     * @var IObserver|null
     */
    protected $observer = null;

    public function __construct()
    {
        parent::__construct();
        $this->observer = null;
    }

    public function setObserver(IObserver $observer)
    {
        $this->observer = $observer;
    }

    public function getObserver() : ?IObserver
    {
        return $this->observer;
    }

    public function notify(SparkEvent $event)
    {
        if ($this->observer) {
            $this->observer->onEvent($event);
        }
    }

}

?>