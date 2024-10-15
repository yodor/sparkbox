<?php
include_once("components/Component.php");

class Marquee extends Component
{
    protected Component $viewport;
    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("Marquee");
        $this->viewport = new Component(false);

    }

    public function requiredStyle() : array
    {
        $result = parent::requiredStyle();
        $result[] = SPARK_LOCAL."/css/Marquee.css";
        return $result;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->viewport->setContents($this->translation_enabled ? tr($this->buffer->get()) : $this->buffer->get());
    }

    protected function renderImpl()
    {
        $this->viewport->render();
        $this->viewport->render();
    }
}

?>