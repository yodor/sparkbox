<?php

interface IDBValue
{
    public function value() : string|float|int|bool|null;

    public function bindingKey() : string|null;

}