<?php
include_once("objects/IObserver.php");
include_once("objects/SparkEvent.php");

/**
 * Default adapter class using Closure callback
 */
class SparkObserver implements IObserver
{
    /**
     * @var Closure
     */
    protected Closure $callback;

    public function __construct(Closure $closure)
    {
        $this->callback = $closure;
    }

    public function setCallback(Closure $closure) : void
    {
        $this->callback = $closure;
    }

    public function getCallback() : Closure
    {
        return $this->callback;
    }

    public function onEvent(SparkEvent $event) : void
    {
        //Debug::ErrorLog("onEvent: ".get_class($event)." - name: ".$event->getName());
        ($this->callback)($event);
    }

}