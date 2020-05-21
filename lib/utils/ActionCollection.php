<?php

class ActionCollection
{

    protected $actions;

    protected $action_matcher;
    protected $content_matcher;
    protected $name_matcher;

    public function __construct()
    {
        $this->actions = array();
        $this->action_matcher = function (Action $act, string $parameter) {
            if (strcmp($act->getAttribute("action"), $parameter) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        $this->content_matcher = function (Action $act, string $parameter) {
            if (strcmp(strip_tags($act->getContents()), $parameter) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        $this->name_matcher = function (Action $act, string $name) {
            if (strcmp($act->getName(), $name) == 0) {
                return TRUE;
            }
            return FALSE;
        };
    }

    public function setActions(array $actions)
    {
        $this->actions = array();
        $iterator = new ArrayIterator($actions);
        while ($iterator->valid()) {
            $action = $iterator->current();
            if ($action instanceof Action) {
                $this->actions[] = $action;
            }
        }
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

    public function append(Action $action)
    {
        $this->actions[] = $action;
    }

    public function insert(Action $action, int $idx)
    {
        array_splice($this->actions, $idx, 0, array($action));
    }

    public function prepend(Action $action)
    {
        $this->insert($action, 0);
    }

    /**
     * Get by 'action' attribute
     * @param string $title
     * @return Action
     */
    public function getByAction(string $action): ?Action
    {

        $idx = $this->index($this->action_matcher, $action);

        //debug("getByAction: $action - idx: $idx");


        return $this->get($idx);

    }

    public function getByContent(string $content): ?Action
    {
        return $this->get($this->index($this->content_matcher, $content));
    }

    public function getByName(string $name): ?Action
    {
        return $this->get($this->index($this->name_matcher, $name));
    }

    public function count(): int
    {
        return count($this->actions);
    }

    public function iterator(): ArrayIterator
    {
        return new ArrayIterator($this->actions);
    }

    public function clear()
    {
        $this->actions = array();
    }

    /**
     * @param int $pos
     * @return Action|null
     */
    public function get(int $pos): ?Action
    {
        $action = NULL;
        if ($pos > -1) {
            if (isset($this->actions[$pos])) {
                $action = $this->actions[$pos];
            }
        }
        return $action;
    }

    /**
     * Remove by '$pos' index and return the action removed
     * @param int $pos
     * @return Action|null
     */
    public function remove(int $pos): ?Action
    {
        $action = $this->get($pos);
        if ($action instanceof Action) {
            unset($this->actions[$pos]);
        }
        return $action;
    }

    public function removeByAction(string $action)
    {
        debug("removeByAction: $action");

        $action = $this->getByAction($action);
        if ($action) {
            $action = $this->remove($this->indexOf($action));
        }
        return $action;
    }

    public function indexOf(Action $other): int
    {
        $pos = -1;

        $instance_matcher = function (Action $action, int $idx) use ($other, &$pos) {
            if ($action === $other) {
                $pos = $idx;
                return TRUE;
            }
            return FALSE;
        };

        $this->each($instance_matcher);

        return $pos;
    }

    public function index(Closure $matcher, string $parameter): int
    {

        $iterator = $this->iterator();
        while ($iterator->valid()) {

            $action = $iterator->current();
            if ($action instanceof Action) {
                if ($matcher($action, $parameter)) return $iterator->key();
            }
            $iterator->next();
        }

        return -1;
    }

    public function each(Closure $closure)
    {
        $iterator = $this->iterator();
        while ($iterator->valid()) {
            $action = $iterator->current();
            if ($action instanceof Action) {
                $ret = $closure($action, $iterator->key());
                if ($ret) break;
            }
            $iterator->next();
        }
    }

    /**
     * Add default query parameter to all actions in this collection
     * @param URLParameter $param
     * @return mixed
     */
    public function addURLParameter(URLParameter $param)
    {
        // TODO: Implement addURLParameter() method.
    }
}