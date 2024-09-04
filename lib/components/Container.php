<?php
include_once("components/Component.php");

class Container extends Component
{
    protected ComponentCollection $items;

    protected bool $enabled = TRUE;

    protected bool $wrapper_enabled = true;

    public function __construct(bool $chained_component_class = true)
    {
        parent::__construct($chained_component_class);
        $this->items = new ComponentCollection();

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Container.css";
        return $arr;
    }

    public function setEnabled(bool $mode) : void
    {
        $this->enabled = $mode;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function items() : ComponentCollection
    {
        return $this->items;
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
        $iterator = $this->items->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof Component)) continue;
            $object->render();
        }
    }

    public function finishRender()
    {
        if ($this->wrapper_enabled) {
            parent::finishRender();
        }
    }

}
