<?php
include_once("input/processors/InputProcessor.php");
include_once("auth/PasswordHash.php");

/**
 * Password field of an account editor:
 *  - the stored hash is never loaded into the field
 *  - an empty value keeps the current password (editing other fields of the account)
 *  - a value is stored as PasswordHash (the format Authenticator::login() verifies)
 */
class PasswordInputProcessor extends InputProcessor
{
    public function loadBeanData(int $editID, DBTableBean $bean, array $data) : void
    {
        $this->input->setValue("");
    }

    public function transactValue(IValueTransactor $transactor) : void
    {
        if ($this->skip_transaction) return;

        $value = (string)$this->input->getValue();
        if ($value === "") {
            Debug::ErrorLog("Password not changed");
            return;
        }

        $transactor->appendValue($this->input->getName(), (new PasswordHash($value))->getValue());
    }
}
