<?php
include_once("forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor implements IValueTransactor
{

    protected ?DBDriver $driver = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function processImpl(InputForm $form) : void
    {
        parent::processImpl($form);

        if (!($this->bean instanceof ConfigBean)) throw new Exception("ConfigBean not set yet");

        $input_names = $form->inputNames();

        $this->driver = DBConnections::Driver();
        try {
            $this->driver->transaction();

            foreach ($input_names as $name) {
                $input = $form->getInput($name);
                $value = $input->getValue();
                //Images are serialized already and are transacted as serialized strings
                //only single StorageObject is supported?
                $input->getProcessor()->transactValue($this);
            }

            $this->driver->commit();
        }
        catch (Exception $e) {
            Debug::ErrorLog("Transaction failed: ".$e->getMessage());
            $this->driver->rollback();
            throw $e;
        }

    }


    public function appendValue(string $key, float|bool|int|string|null $val): void
    {
        if ($this->bean instanceof ConfigBean) {
            $this->bean->set($key, $val, $this->driver);
            return;
        }
        throw new Exception("Incorrect bean - expecting ConfigBean");
    }
}