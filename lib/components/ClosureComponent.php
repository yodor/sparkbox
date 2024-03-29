<?php

class ClosureComponent extends Container
{
    protected $wrapper_enabled = true;

    protected $closure = null;

    public function __construct(Closure $callback, bool $wrapper_enabled = true)
    {
        parent::__construct();
        $this->closure = $callback;
        $this->wrapper_enabled = $wrapper_enabled;
    }

    public function getCloser() : Closure
    {
        return $this->closure;
    }

    public function setClosure(Closure $callback)
    {
        $this->closure = $callback;
    }

    protected function renderImpl()
    {
        parent::renderImpl();
        $closure = $this->closure;
        $closure($this);
    }
}

?>