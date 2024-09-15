<?php
include_once("responders/RequestResponder.php");

class DeleteItemResponder extends RequestResponder
{

    protected int $item_id = -1;
    protected DBTableBean $bean;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct("delete_item");
        $this->bean = $bean;
        $this->need_confirm = TRUE;
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
        return parent::getParameterNames() + array("item_id");
    }

    public function getItemID() : int
    {
        return $this->item_id;
    }

    public function createAction(string $title = "Delete", string $href = "", Closure $check_code = NULL, array $parameters = array()) : ?Action
    {
        $parameters[] = new DataParameter("item_id", $this->bean->key());
        return new Action($title, "?cmd={$this->cmd}&$href", $parameters, $check_code);
    }

    protected function processConfirmation() : void
    {
        $this->setupConfirmDialog("Delete", "Confirm you want to delete this item?");
    }

    /**
     * @throws Exception
     */
    protected function processImpl() : void
    {

        debug("Deleting ID: $this->item_id of DBTableBean: ".get_class($this->bean));

        $this->bean->delete($this->item_id);

    }

}

?>
