<?php
include_once("objects/ComponentCollection.php");
include_once("utils/URL.php");
include_once("components/Action.php");

class ActionCollection extends ComponentCollection
{

    /**
     * Add URLParameter $param to all actions in this collection
     * @param URLParameter $param
     */
    public function addURLParameter(URLParameter $param)
    {

        foreach ($this->elements as $action) {
            if ($action instanceof Action) {
                $action->getURL()->add($param);
            }
        }
    }

    /**
     * Call set data method on each of the actions in this collection
     * @param array $data
     */
    public function setData(array $data) : void
    {
        foreach ($this->elements as $action) {
            if ($action instanceof Action) {
                $action->setData($data);
            }
        }
    }
}
