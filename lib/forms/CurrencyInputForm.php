<?php
include_once("forms/InputForm.php");

class CurrencyInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();
        $field = DataInputFactory::Create(DataInputFactory::TEXT, "currency_code", "ISO3 Code", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "symbol", "Symbol", 1);
        $this->addInput($field);

    }

}

?>