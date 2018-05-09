<?php
include_once("lib/forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor {

    public function __construct()
    {

    }
    
    protected function processImpl(InputForm $form)
    {
        parent::processImpl($form);
            
        if ($this->getStatus() != FormProcessor::STATUS_OK) return;

        global $config;
        
        foreach($form->getFields() as $field_name=> $field) {
            $field_value = $field->getValue();
            $config->setValue($field_name, $field_value); 
        }
        
    }

}

?>
