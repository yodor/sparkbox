<?php
include_once("forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function processImpl(InputForm $form)
    {
        parent::processImpl($form);

        if ($this->getStatus() != FormProcessor::STATUS_OK) return;

        global $config;

        foreach ($form->getInputs() as $field_name => $field) {
            $field_value = $field->getValue();
            $config->setValue($field_name, $field_value);
        }

    }

}

?>
