<?php
include_once("utils/output/OutputScript.php");

class LDJsonScript extends OutputScript
{
    protected array $data = array();

    public function __construct(array $data)
    {
        parent::__construct();
        $this->data = $data;
    }

    public function setData(array $data) : void
    {
        $this->data = $data;
    }

    protected function fillBuffer(): void
    {
        if (count($this->data)<1) return;

        echo "\n<script type='application/ld+json'>\n";
        echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n</script>\n";

    }
}
?>
