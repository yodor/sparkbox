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
        $result[] = Spark::Get(Config::SPARK_LOCAL)."/css/Marquee.css";
        return $result;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $value = $this->buffer()->get();

        $value = str_replace(array("\\r\\n", "\\r", "\\n"), ' ', $value);
        $value = strip_tags(stripslashes($value));

        $this->viewport->setContents($value);
    }

    protected function renderImpl(): void
    {
        $this->viewport->render();
        $this->viewport->render();
    }
}