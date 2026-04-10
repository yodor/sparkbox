<?php
include_once("components/Script.php");

/**
 * Inline page javascript
 */
class InlineScript extends Script
{
    /**
     * @var bool Wrap script contents inside onPageLoad function
     */
    protected bool $onPageLoad = false;

    /**
     * Inline page javascript code component
     *
     * @param bool $onPageLoad Wraps script contents inside onPageLoad function
     */
    public function __construct(bool $onPageLoad = false)
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

    protected function finalize(): void
    {
        //take the current state of $code (might be set from sub-class)
        $code = $this->getCode();

        if (!empty($code) && $this->onPageLoad) {
            $code = "onPageLoad(function(){\n$code\n});";
            $this->setCode($code);
        }

        //call parent to do setContents($this->code)
        parent::finalize();
    }
}