<?php
include_once("components/Component.php");

class TextComponent extends Component
{
    protected $tagName = "SPAN";

    public function __construct(string $text)
    {
        parent::__construct();
        $this->contents = $text;
    }

}