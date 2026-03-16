<?php

interface ISQLBinding {

    /**
     * Get the value to be used as binding key during prepared statement binding.
     *
     * Returning empty string here signals the binding collectors calling IBindingCollection->getBindings() to not use
     * the object getBindingValue().
     *
     * @return string Value to use during named parameter binding - ex ':name'
     */
    public function getBindingKey() : string;

    /**
     * Get the value to be used as binding value during prepared statement binding.
     *
     * @return string|int|float|bool|null value to be used as binding value
     * @throws Exception if bindingKey is empty or value is not SQLStatement::IsBoundSafe
     */
    public function getBindingValue() : string|int|float|bool|null;

}