<?php
include_once("objects/ActionCollection.php");

interface IActionCollection
{

    public function setActions(ActionCollection $actions): void;

    public function getActions(): ?ActionCollection;

}