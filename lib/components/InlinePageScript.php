<?php
include_once("components/Script.php");
include_once("components/renderers/IPageScript.php");

abstract class InlinePageScript extends Script implements IPageScript
{
    protected bool $onPageLoad = true;

    public function __construct(bool $onPageLoad = true)
    {
        parent::__construct();
        $this->onPageLoad = $onPageLoad;
    }

    /**
     * Enable onPageLoad wrapper.
     *
     * code() returned string will be treated as contents of function parameter to onPageLoad()
     *
     * ex onPageLoad(function() {$code} );
     *
     * @return void
     */
    public function enableOnPageLoad() : void
    {
        $this->onPageLoad = true;
    }

    /**
     * Disable onPageLoad wrapper
     *
     * code() will be written directly
     *
     * @return void
     */
    public function disableOnPageLoad() : void
    {
        $this->onPageLoad = false;
    }

    abstract public function code() : string;

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $code = $this->code();
        if ($this->onPageLoad) {
            $code = <<<JS
onPageLoad(function () {
$code
});
JS;
        }
        $this->setContents($code);
    }
}