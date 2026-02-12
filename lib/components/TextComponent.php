<?php
include_once("components/Component.php");

class TextComponent extends Component
{

    public function __construct(string $contents="", string $componentClass="TextComponent")
    {
        parent::__construct();
        $this->tagName = "span";

        $this->buffer->set($contents);

        $this->setComponentClass($componentClass);
    }

}