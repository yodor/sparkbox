<?php
include_once("responders/RequestResponder.php");

include_once("dialogs/ConfirmMessageDialog.php");

class ToggleFieldResponder extends RequestResponder
{

    protected $item_id;
    protected $bean;
    protected $status;
    protected $field_name;

    public function __construct(DBTableBean $bean, $cmd = "toggle_field")
    {
        parent::__construct($cmd);

        $this->bean = $bean;

        $this->need_confirm = TRUE;
    }

    protected function parseParams()
    {
        if (!isset($_GET["item_id"])) throw new Exception("Item ID not passed");
        if (!isset($_GET["status"])) throw new Exception("Status not passed");
        if (!isset($_GET["field"])) throw new Exception("Field not passed");

        $this->item_id = (int)$_GET["item_id"];
        $this->status = ((int)$_GET["status"] > 0) ? 1 : 0;
        $this->field_name = $_GET["field"];

        $arr = $_GET;
        unset($arr["cmd"]);
        unset($arr["item_id"]);
        unset($arr["status"]);
        unset($arr["field"]);

        $this->cancel_url = queryString($arr);
        $this->cancel_url = $_SERVER['PHP_SELF'] . $this->cancel_url;

        $this->success_url = $this->cancel_url;

    }

    public function createAction($title = "Toggle", $href_add = "", $check_code = NULL, $parameters_array = array())
    {

        $parameters = array(new DataParameter("item_id", $this->bean->key()));

        return new Action($title, "?cmd={$this->cmd}&$href_add", array_merge($parameters, $parameters_array), $check_code);

    }

    protected function processImpl()
    {

        $field_name = DBConnections::Get()->escape($this->field_name);

        $update_row[$field_name] = $this->status;

        $this->bean->update($this->item_id, $update_row);

    }

}

?>