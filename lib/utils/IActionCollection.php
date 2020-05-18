<?php
include_once("utils/ActionCollection.php");

interface IActionCollection
{

    public function setActions(ActionCollection $actions);

    public function getActions(): ?ActionCollection;

//    /**
//     * Add default query parameter to all actions in this collection
//     * @param URLParameter $param
//     * @return mixed
//     */
//    public function addURLParameter(URLParameter $param);


}