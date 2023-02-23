<?php
include_once("objects/IObserver.php");

/**
 * Default adapter class using Closure callback if set
 */
class SparkObserver implements IObserver
{
    /**
     * @var Closure
     */
    protected $callback = NULL;

    public function __construct()
    {

    }

    public function setCallback(Closure $closure)
    {
        $this->callback = $closure;
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    public function onEvent(SparkEvent $event)
    {
        debug("SparkObserver onEvent: ".$event->getName()." | Source: ".get_class($event->getSource()));

        if ($this->callback instanceof Closure) {
            $observer = $this->callback;
            $observer($event);
        }

    }

}
?>