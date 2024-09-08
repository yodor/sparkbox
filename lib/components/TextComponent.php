<?php
include_once("components/Component.php");

class TextComponent extends Component
{

    public function __construct(string $contents="")
    {
        parent::__construct();
        $this->tagName = "SPAN";

        $this->buffer->set($contents);

        $this->setComponentClass("TextComponent");
    }

}
?>
