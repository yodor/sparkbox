<?php
include_once("objects/SparkList.php");
include_once("objects/SparkList.php");

class ComponentCollection extends SparkList {

    protected Closure $attribute_matcher;
    protected Closure $content_matcher;
    protected Closure $name_matcher;

    public function __construct()
    {
        parent::__construct();

        $this->attribute_matcher = function (Component $object, mixed ...$args) {
            list($value, $attribute_name) = $args;

            if (strcmp($object->getAttribute($attribute_name), $value) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        $this->content_matcher = function (Component $object, string $parameter) {
            if (strcmp(strip_tags($object->getContents()), $parameter) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        $this->name_matcher = function (Component $object, string $name) {
            if (strcmp($object->getName(), $name) == 0) {
                return TRUE;
            }
            return FALSE;
        };
    }


    public function getByAction(string $action_name): ?Component
    {
        return $this->getByAttribute($action_name, "action");
    }

    public function getByAttribute(string $value, string $attribute_name): ?Component
    {
        $idx = $this->index($this->attribute_matcher, $value, $attribute_name);
        if (is_null($idx))return null;
        //debug("getByAction: $action - idx: $idx");
        $object = $this->get($idx);
        if ($object instanceof Action) return $object;
        return null;
    }

    public function getByContent(string $content): ?Component
    {
        $idx = $this->index($this->content_matcher, $content);
        if (is_null($idx))return null;
        //debug("getByContent: $content - idx: $idx");
        $object = $this->get($idx);
        if ($object instanceof Action) return $object;
        return null;
    }

    public function getByName(string $name): ?Component
    {
        $idx = $this->index($this->name_matcher, $name);
        if (is_null($idx))return null;
        //debug("getByName: $name - idx: $idx");
        $object = $this->get($idx);
        if ($object instanceof Action) return $object;
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
     * Search component by calling Closure
     * If closure return true the key is returned otherways returns null
     * @param Closure $matcher
     * @param mixed ...$args
     * @return int|string|null
     */
    protected function index(Closure $matcher, string ...$args) : int|string|null
    {
        $iterator = $this->iterator();
        $key = null;
        while ($object = $iterator->next()) {
            if ($matcher($object, ...$args)) {
                $key = $iterator->key();
                break;
            }
        }
        return $key;
    }

    /**
     * Calls Closure on each element until closure returns true
     * If closure does not return true all elements are iterated
     * Closure is called with parameters ($object,$key)
     * @param Closure $closure
     * @return void
     */
    public function each(Closure $closure) : void
    {
        $iterator = $this->iterator();
        while ($object = $iterator->next()) {
            $ret = $closure($object, $iterator->key());
            if ($ret) break;
        }
    }


}

?>
