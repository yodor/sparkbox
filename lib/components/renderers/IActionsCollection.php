<?php
include_once("components/Action.php");
include_once("utils/URLParameter.php");

interface IActionsCollection
{
    public function addAction(Action $a);

    public function getAction(string $title): Action;

    public function setActions(array $actions);

    public function getActions(): ?array;

    /**
     * Add default query parameter to all actions in this collection
     * @param URLParameter $param
     * @return mixed
     */
    public function addURLParameter(URLParameter $param);

}