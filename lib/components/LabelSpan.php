<?php
include_once("components/Container.php");

class LabelSpan extends Container
{
    protected Component $label;
    protected Component $span;

    public function __construct(string $label_contents="", string $span_contents="")
    {
        parent::__construct(false);
        $this->setComponentClass("");

        $label = new Component(false);
        $label->setTagName("span");
        $label->setComponentClass("label");
        $label->setContents($label_contents);
        $this->items()->append($label);
        $this->label = $label;

        $span = new Component(false);
        $span->setTagName("span");
        $span->setComponentClass("");
        $span->setContents($span_contents);
        $this->items()->append($span);
        $this->span = $span;
    }

    public function label() : Component
    {
        return $this->label;
    }

    public function span() : Component
    {
        return $this->span;
    }

}

?>
