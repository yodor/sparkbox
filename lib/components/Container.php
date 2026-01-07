<?php
include_once("components/Component.php");

class Container extends Component
{
    /**
     * Collection of items to be rendered in addition to the contents buffer
     * @var ComponentCollection
     */
    protected ComponentCollection $items;

    /**
     * Flag controlling opening/closing tag rendering.
     * If set to false will prevent calling the start/finish render methods of the parent Component class.
     * Only renderImpl will be executed rendering the content buffer and all the elements in the ComponentCollection.
     * @var bool
     */
    protected bool $wrapper_enabled = true;

    /**
     * Flag controlling component collection rendering position
     * If set to true will render items from the component collection first, before the contents buffer
     * @var bool
     */
    protected bool $items_first = false;

    function __clone()
    {
        if ($this->items instanceof ComponentCollection) {
            $this->items = clone $this->items;
        }
    }

    public function __construct(bool $chained_component_class = true)
    {
        parent::__construct($chained_component_class);
        $this->items = new ComponentCollection();
        $this->items->setParent($this);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Container.css";
        return $arr;
    }

    public function items() : ComponentCollection
    {
        return $this->items;
    }

    public function startRender(): void
    {
        if (!$this->wrapper_enabled) return;
        parent::startRender();
    }

    protected function renderImpl(): void
    {
        if ($this->items_first) {
            $this->renderCollectionItems();
            parent::renderImpl();
        }
        else {
            parent::renderImpl();
            $this->renderCollectionItems();
        }
    }

    private function renderCollectionItems() : void
    {
        $iterator = $this->items->iterator();
        while ($object = $iterator->next()) {
            if (!($object instanceof Component)) continue;
            $object->render();
        }
    }

    public function finishRender(): void
    {
        if (!$this->wrapper_enabled) return;
        parent::finishRender();
    }


}
