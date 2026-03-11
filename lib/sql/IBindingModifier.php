<?php

interface IBindingModifier {

    /**
     * Append custom binding to the binding collection. Throws exception if binding key is empty.
     *
     * @param string $bindingKey
     * @param array|string|int|float|bool|null $value
     * @return void
     * @throws Exception If bindingKey is empty
     */
    public function bind(string $bindingKey, array|string|int|float|bool|null $value) : void;
}