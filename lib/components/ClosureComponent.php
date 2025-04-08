<?php

class ClosureComponent extends Container
{

    protected ?Closure $closure = null;

    public function __construct(?Closure $callback = null, bool $wrapper_enabled = true, bool $chained_component_class = true)
    {
        parent::__construct($chained_component_class);
        $this->closure = $callback;
        $this->wrapper_enabled = $wrapper_enabled;
    }

    public function getClosure() : ?Closure
    {
        return $this->closure;
    }

    public function setClosure(Closure $callback) : void
    {
        $this->closure = $callback;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if (is_null($this->closure)) {
            $this->setRenderEnabled(false);
        }
    }

    protected function renderImpl()
    {
        parent::renderImpl();
        ($this->closure)($this);
    }
}

?>
