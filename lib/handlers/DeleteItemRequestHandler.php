<?php
include_once("handlers/RequestHandler.php");

class DeleteItemRequestHandler extends RequestHandler
{

    protected $item_id;
    protected $bean;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct("delete_item");
        $this->bean = $bean;
        $this->need_confirm = TRUE;
    }

    protected function parseParams()
    {
        if (!isset($_GET["item_id"])) throw new Exception("Item ID not passed");
        $this->item_id = (int)$_GET["item_id"];
        $arr = $_GET;
        unset($arr["cmd"]);
        unset($arr["item_id"]);
        $this->cancel_url = queryString($arr);
        $this->cancel_url = $_SERVER['PHP_SELF'] . $this->cancel_url;

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

    protected function processConfirmation()
    {
        $this->drawConfirmDialog("Confirm Delete", "Confirm you want to delete this item?");
    }

    protected function process()
    {

        $db = DBConnections::Factory();

        try {

            $db->transaction();
            if (!$this->bean->deleteID($this->item_id, $db)) throw new Exception("Unable to delete item: " . $db->getError());
            $db->commit();

        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    }

}

?>