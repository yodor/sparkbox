<?php

interface IValueTransactor
{
    /**
     * Add value to this transactor values. Will be commited with the main transaction
     * @param string $key
     * @param string|float|int|bool|null $val
     */
    public function appendValue(string $key, string|float|int|bool|null $val) : void;
}