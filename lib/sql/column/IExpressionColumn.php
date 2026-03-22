<?php
include_once("sql/IBindingModifier.php");

interface IExpressionColumn
{
    public function set(string $expression) : IBindingModifier;
}