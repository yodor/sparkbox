<?php
include_once("input/processors/InputProcessor.php");

/**
 * A secret of a configuration (an API password) in a PASSWORD field: the page never shows it (PasswordField has no
 * value), an empty value keeps the stored one, a value replaces it.
 */
class SecretInputProcessor extends InputProcessor
{
    public function transactValue(IValueTransactor $transactor) : void
    {
        if ($this->skip_transaction) return;
        $value = (string)$this->input->getValue();
        if ($value === "") return;
        $transactor->appendValue($this->input->getName(), $value);
    }
}
