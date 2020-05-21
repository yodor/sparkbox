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

        global $config;

        $input_names = $form->getInputNames();
        foreach ($input_names as $idx => $name) {
            $input = $form->getInput($name);
            if ($this->bean instanceof ConfigBean) {
                $this->bean->setValue($name, $input->getValue());
            }

        }
    }

}

?>
