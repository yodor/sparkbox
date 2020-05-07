<?php

interface IActionsCollection
{
    public function addAction(Action $a);
    public function getAction(string $title) : Action;

    public function setActions(array $actions);
    public function getActions() : ?array;

}