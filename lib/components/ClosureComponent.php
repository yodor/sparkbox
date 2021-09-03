<?php

class ClosureComponent extends Component
{
    protected $closure = null;

    public function __construct(Closure $callback)
    {
        parent::__construct();
        $this->closure = $callback;
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