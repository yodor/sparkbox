<?php
include_once("objects/SparkEvent.php");
include_once("objects/SparkObserver.php");

class SparkEventManager
{
    /**
     * Reference to all subscribed observers by class_name and list of observers
     * array("SomeEvent" => array("0"=>IObservable1, "1"=>IObservable2), "BeanEvent" => ...etc.)
     * @var array
     */
    protected static array $subscribers = array();

    private function __construct()
    {

    }

    /**
     * Register observer for receiving events of given class name
     * @param string $event_class
     * @param IObserver $observer
     * @return void
     * @throws Exception if event_class is not subclass of SparkEvent
     */
    public static function register(string $event_class, IObserver $observer): void
    {
        if (!class_exists($event_class)) {
            SparkLoader::Factory(SparkLoader::PREFIX_EVENTS)->define($event_class);
        }

        if (!class_exists($event_class)) {
            throw new Exception("Class not defined: $event_class");
        }

        if (!is_subclass_of($event_class, SparkEvent::class)) throw new Exception("Incorrect event_class - expecting SparkEvent subclass");
        $parentClass = "NULL";
        if ($observer instanceof SparkObserver) {
            if ($observer->getParent()) {
                $parentClass = get_class($observer->getParent());
            }
        }
        Debug::ErrorLog(get_class($observer) . "[$parentClass] observing $event_class");
        self::$subscribers[$event_class][] = $observer;
    }

    public static function unregister(string $event_class, IObserver $observer): void
    {
        foreach (self::$subscribers[$event_class] as $index => $subscriber) {
            if ($subscriber === $observer) {
                Debug::ErrorLog("Unregistering " . get_class($observer) . " from observing $event_class");
                unset(self::$subscribers[$event_class][$index]);
            }
        }
    }

    public static function unregisterClosure(string $event_class, Closure $closure): void
    {
        foreach (self::$subscribers[$event_class] as $index => $subscriber) {
            if ($subscriber instanceof SparkObserver) {
                if ($subscriber->getCallback() === $closure) {
                    Debug::ErrorLog("Unregistering closure from observing $event_class");
                    unset(self::$subscribers[$event_class][$index]);
                }
            }
        }
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
            //Debug::ErrorLog("$event_class does not have observers");
            return;
        }

        //Debug::ErrorLog("Emiting $event_class to ".count($observers)." registered observers");

        foreach ($observers as $observer) {
            $observer->onEvent($event);
        }
    }

}