<?php
include_once("components/Container.php");

class PageSection extends Container
{
    protected Container $space_left;
    protected Container $space_right;
    protected Container $content;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("section");

        $this->space_left = new Container(false);
        $this->space_left->setComponentClass("space left");
        $this->items()->append($this->space_left);

        $this->content = new Container(false);
        $this->content->setComponentClass("content");
        $this->items()->append($this->content);

        $this->space_right = new Container(false);
        $this->space_right->setComponentClass("space right");
        $this->items()->append($this->space_right);
    }

    public function spaceLeft() : Container
    {
        return $this->space_left;
    }

    public function content() : Container
    {
        return $this->content;
    }

    public function spaceRight() : Container
    {
        return $this->space_right;
    }
}

?>