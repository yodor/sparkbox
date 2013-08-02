<?php
include_once("lib/actions/Action.php");

interface IActionsRenderer 
{


	public function addAction(Action $a);
	
	public function setActions(array $actions);
	public function getActions();
	public function renderActions(array &$row);
}

?>