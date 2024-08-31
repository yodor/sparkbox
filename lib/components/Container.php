<?php
include_once("components/Component.php");

class Container extends Component
{
    protected array $items = array();

    protected bool $enabled = TRUE;

    protected bool $wrapper_enabled = TRUE;

    public function __construct()
    {
        parent::__construct();
        $this->items = array();
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Container.css";
        return $arr;
    }

    public function setWrapperEnabled(bool $mode)
    {
        $this->wrapper_enabled = $mode;
    }

    public function isWrapperEnabled(): bool
    {
        return $this->wrapper_enabled;
    }

    public function setEnabled(bool $mode)
    {
        $this->enabled = $mode;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function clear()
    {
        $this->items = array();
    }

    public function insert(Component $cmp, int $idx)
    {
        array_splice($this->items, $idx, 0, array($cmp));
    }

    public function prepend(Component $cmp)
    {
        $this->insert($cmp, 0);
    }


    /**
     * @param Component $cmp
     * @return void
     */
    public function append(Component $cmp) : void
    {
        $this->items[] = $cmp;
    }

    /**
     * Return the component at index $idx
     * @param int $idx The index of the component in this container
     * @return Component
     */
    public function get(int $idx): Component
    {
        return $this->items[$idx];
    }

    public function getByName(string $name): Component
    {
        $comparator = function (Component $cmp) use ($name) {
            if (strcmp($name, $cmp->getName()) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        return $this->findBy($comparator);

    }

    public function getByClassName(string $name): Component
    {
        $comparator = function (Component $cmp) use ($name) {
            if (strcmp($name, $cmp->getClassName()) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        return $this->findBy($comparator);

    }

    public function getByAttribute(string $name, string $value): Component
    {
        $comparator = function (Component $cmp) use ($name, $value) {
            if (strcmp($value, $cmp->getAttribute($name)) == 0) {
                return TRUE;
            }
            return FALSE;
        };
        return $this->findBy($comparator);
    }

    public function getByAction(string $action_value): Component
    {
        return $this->getByAttribute("action", $action_value);
    }

    public function findBy(Closure $callback): ?Component
    {
        $indexes = array_keys($this->items);
        foreach ($indexes as $index) {
            $component = $this->get($index);
            if ($callback($component)) {
                return $component;
            }
        }
        return NULL;
    }

    public function startRender()
    {
        if ($this->wrapper_enabled) {
            parent::startRender();
        }
    }

    protected function renderImpl()
    {
        parent::renderImpl();
        $indexes = array_keys($this->items);
        foreach ($indexes as $index) {
            $component = $this->get($index);
            $component->render();
        }
    }

    public function finishRender()
    {
        if ($this->wrapper_enabled) {
            parent::finishRender();
        }
    }

}
