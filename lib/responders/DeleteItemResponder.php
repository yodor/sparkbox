<?php
include_once("responders/RequestResponder.php");

class DeleteItemResponder extends RequestResponder
{

    protected int $item_id = -1;
    protected DBTableBean $bean;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct();
        $this->bean = $bean;
        $this->need_confirm = TRUE;
        $this->confirm_dialog_title = "Delete";
        $this->confirm_dialog_text = "Confirm you want to delete this item?";
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        if (!$this->url->contains("item_id")) throw new Exception("Item ID not passed");
        $this->item_id = (int)$this->url->get("item_id")->value();
    }

    public function getParameterNames() : array
    {
        $result = parent::getParameterNames();
        $result[] = "item_id";
        return $result;
    }

    public function getItemID() : int
    {
        return $this->item_id;
    }

    public function createAction(string $title = "Delete") : ?Action
    {
        $action = parent::createAction($title);
        $action->getURL()->add(new DataParameter("item_id", $this->bean->key()));
        return $action;
    }

    /**
     * @throws Exception
     */
    protected function processImpl() : void
    {

        Debug::ErrorLog("Deleting ID: $this->item_id of DBTableBean: ".get_class($this->bean));

        $this->bean->delete($this->item_id);

    }

}