<?php
include_once("forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor implements IValueTransactor
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
            $input->getProcessor()->transactValue($this);
        }
    }

    /**
     * Add value to this transactor values. Will be commited with the main transaction
     * @param string $key
     * @param $value
     */
    public function appendValue(string $key, $val) : void
    {
        if ($val instanceof StorageObject) {
            $val = serialize($val);
        }
        $this->bean->set($key, $val);
    }

}