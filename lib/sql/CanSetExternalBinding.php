<?php

trait CanSetExternalBinding {

    /**
     * @var array<string, string|int|float|bool|null>
     */
    protected array $externalBindings = array();

    public function bind(string $bindingKey, string|int|float|bool|null $value) : void
    {
        if (!$bindingKey) throw new Exception("bindingKey is empty");
        if (!SQLStatement::IsBindingKeySafe($bindingKey)) throw new Exception("Binding key is not safe");

        $this->externalBindings[$bindingKey] = $value;
    }

    protected function copyExternaBindingsTo(self $target) : void
    {
        foreach ($this->externalBindings as $bindingKey => $bindingValue) {
            $target->externalBindings[$bindingKey] = $bindingValue;
        }
    }

}