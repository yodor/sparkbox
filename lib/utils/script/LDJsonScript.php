<?php
include_once("components/Script.php");

class LDJsonScript extends Script
{

    protected string $context = "https://schema.org";

    public function __construct()
    {
        parent::__construct();
        $this->setType("application/ld+json");
    }

    public function setContext(string $context) : void
    {
        $this->context = $context;
    }

    public function getContext() : string
    {
        return $this->context;
    }

    public function setLinkedData(LinkedData $linkedData): void
    {
        $ldArray = $linkedData->toArray();

        //no type or data - do nothing
        if (empty($linkedData->getType()) && count($ldArray) === 0) {
            $this->setCode("");
            return;
        }

        $data = array("@context" => $this->context) + $ldArray;

        $json = json_encode($data,
            JSON_THROW_ON_ERROR |
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );
        $this->setCode($json);

    }

    public function render(): void
    {
        if (empty($this->getCode())) {
            $this->setRenderEnabled(false);
        }
        parent::render();
    }
}