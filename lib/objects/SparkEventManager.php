<?php

class SparkEventManager
{
    /**
     * Reference to all subscribed observers by class_name and list of observers
     * array("SomeEvent" => array("0"=>IObservable1, "1"=>IObservable2), "BeanEvent" => ...etc)
     * @var array
     */
    protected static array $subscribers = array();

    private function __construct()
    {

    }

    /**
     * Register observer for receving events of given class name
     * @param string $event_class
     * @param IObserver $observer
     * @return void
     * @throws Exception if event_class is not subclass of SparkEvent
     */
    public static function register(string $event_class, IObserver $observer) : void
    {
        if (!is_subclass_of($event_class, 'SparkEvent')) throw new Exception("Incorrect event_class - expecting SparkEvent subclass");
        debug("Registering observer ".get_class($observer)." with event class: $event_class");
        self::$subscribers[$event_class][] = $observer;
    }

    public static function subscribedEvents() : array
    {
        return array_keys(self::$subscribers);
    }

    private static function observersForEvent(SparkEvent $event) : ?array
    {
        $observers = null;
        $subscribed_events = self::subscribedEvents();
        foreach ($subscribed_events as $class_name) {
            if ($event instanceof $class_name) {
                $observers = self::$subscribers[$class_name];
                break;
            }
        }
        return $observers;
    }

    /**
     * Called from subjects to message observers about event
     * @param SparkEvent $event
     * @return void
     */
    public static function emit(SparkEvent $event) : void
    {
        $event_class = get_class($event);
        $observers = self::observersForEvent($event);

        if (is_null($observers)) {
            debug("Event $event_class does not have registered observers");
            return;
        }

        debug("Emiting $event_class to ".count($observers)." registered observers");

        foreach ($observers as $observer) {
            debug("Calling observer: ".get_class($observer));
            $observer->onEvent($event);
        }
    }

}
?>
