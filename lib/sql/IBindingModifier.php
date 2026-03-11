<?php

interface IBindingModifier {
    public function bind(string $bindingKey, string|array $value) : void;
}