<?php
include_once("components/Script.php");

class LDJsonScript extends Script
{
    protected array $data = array();

    public function __construct(array $data)
    {
        parent::__construct();
        $this->setType("application/ld+json");
        $this->setData($data);
    }

    public function setData(array $data) : void
    {
        $this->data = $data;
        $this->setContents(json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function processAttributes(): void
    {
        if (count($this->data)<1) {
            $this->setRenderEnabled(false);
            return;
        }
        parent::processAttributes();
    }
}
?>
