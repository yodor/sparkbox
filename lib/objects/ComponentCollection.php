<?php
include_once("objects/SparkList.php");

class ComponentCollection extends SparkList {

    public function __construct()
    {
        parent::__construct();
    }

    public function getByAction(string $action_name): ?Component
    {
        return $this->getByAttribute($action_name, "action");
    }

    public function getByAttribute(string $value, string $attribute_name): ?Component
    {
        $comparator = function (Component $object, mixed ...$args) {
            list($value, $attribute_name) = $args;

            if (strcmp($object->getAttribute($attribute_name), $value) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $value, $attribute_name);
        if (is_null($idx))return null;

        $object = $this->get($idx);
        if ($object instanceof Component) return $object;
        //debug("getByAction: $action - idx: $idx");
        return null;
    }

    public function getByContent(string $parameter): ?Component
    {
        $comparator = function (Component $object, string $parameter) {
            if (strcmp(strip_tags($object->getContents()), $parameter) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $parameter);
        if (is_null($idx))return null;
        //debug("getByContent: $content - idx: $idx");
        $object = $this->get($idx);
        if ($object instanceof Component) return $object;
        return null;
    }

    public function getByName(string $name): ?Component
    {
        $comparator = function (Component $object, string $name) {
            if (strcmp($object->getName(), $name) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $name);
        if (is_null($idx))return null;
        //debug("getByName: $name - idx: $idx");
        $object = $this->get($idx);
        if ($object instanceof Component) return $object;
        return null;
    }

    public function getByClassName(string $name): ?Component
    {
        $comparator = function (Component $cmp, string $name)  {
            if (strcmp($name, $cmp->getClassName()) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $name);
        if (is_null($idx)) return null;
        $object = $this->get($idx);
        if ($object instanceof Component) return $object;
        return null;

    }

    public function getByComponentClass(string $name): ?Component
    {
        $comparator = function (Component $cmp, string $name)  {
            if (strcmp($name, $cmp->getComponentClass()) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $name);
        if (is_null($idx)) return null;
        $object = $this->get($idx);
        if ($object instanceof Component) return $object;
        return null;

    }

    public function getByContainerClass(string $name): ?Container
    {
        $comparator = function (Component $cmp, string $name)  {
            if (strcmp($name, $cmp->getComponentClass()) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $idx = $this->index($comparator, $name);
        if (is_null($idx)) return null;
        $object = $this->get($idx);
        if ($object instanceof Container) return $object;
        return null;

    }

    public function removeByAction(string $action) : void
    {
        debug("removeByAction: $action");

        $action = $this->getByAction($action);
        if ($action) {
            $this->removeAll($action);
        }
    }

    /**
     * Find first index of component by calling Closure
     * If closure return true the key is returned otherways returns null
     * @param Closure $matcher
     * @param mixed ...$args
     * @return int|string|null
     */
    public function index(Closure $matcher, string ...$args) : int|string|null
    {
        $key = null;
        $iterator = $this->iterator();
        while ($object = $iterator->next()) {
            if ($matcher($object, ...$args)) {
                $key = $iterator->key();
                break;
            }
        }
        return $key;
    }

    /**
     * Closure Iterator with parameters ($object,$key) until result from calling Closure is true
     * If closure does not return true all elements are iterated
     * @param Closure $closure
     * @return void
     */
    public function each(Closure $closure) : void
    {
        $iterator = $this->iterator();
        while ($object = $iterator->next()) {
            $ret = $closure($object, $iterator->key());
            if ($ret === true) break;
        }
    }


}

?>
