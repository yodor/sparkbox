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

    protected function parseParams()
    {
        if (!$this->url->contains("item_id")) {
            throw new Exception("Item ID not passed");
        }

        $this->item_id = (int)$this->url->get("item_id")->value();

    }

    protected function buildRedirectURL() : void
    {
        parent::buildRedirectURL();
        $this->url->remove("item_id");
    }

    public function getItemID()
    {
        return $this->item_id;
    }

    public function createAction($title = "Delete", $href_add = "", $check_code = NULL, $parameters_array = array())
    {
        $parameters = array(new DataParameter("item_id", $this->bean->key()));
        return new Action($title, "?cmd=delete_item$href_add", array_merge($parameters, $parameters_array), $check_code);
    }

    protected function processConfirmation() : void
    {
        $this->setupConfirmDialog("Delete", "Confirm you want to delete this item?");
    }

    /**
     * @throws Exception
     */
    protected function processImpl()
    {

        debug("Deleting ID: $this->item_id of DBTableBean: ".get_class($this->bean));

        $this->bean->delete($this->item_id);

    }

}

?>
