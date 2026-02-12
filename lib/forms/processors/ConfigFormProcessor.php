<?php
include_once("forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor
{

    public function __construct()
    {
        parent::__construct();
    }


    protected function processImpl(InputForm $form) : void
    {
        parent::processImpl($form);

        if (!($this->bean instanceof ConfigBean)) throw new Exception("ConfigBean not set yet");

        $input_names = $form->inputNames();
        foreach ($input_names as $name) {

            $input = $form->getInput($name);
            $this->bean->set($name, $input->getValue());

        }
    }

}