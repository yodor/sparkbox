<?php

class ClosureComponent extends Component
{
    protected $closure = null;
    public function __construct(Closure $callback)
    {
        parent::__construct();
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