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
        if (!$this->url->contains("item_id")) {
            throw new Exception("Item ID not passed");
        }
        $this->item_id = (int)$this->url->get("item_id")->value();

        if (!$this->url->contains("status")) {
            throw new Exception("Status not passed");
        }
        $this->status = ((int)$this->url->get("status")->value() > 0) ? 1 : 0;

        if (!$this->url->contains("field")) {
            throw new Exception("Field not passed");
        }
        $this->field_name = $this->url->get("field")->value();

    }

    protected function buildRedirectURL() : void
    {
        parent::buildRedirectURL();
        $this->url->remove("item_id");
        $this->url->remove("status");
        $this->url->remove("field");
    }

    public function createAction($title = "Toggle", $href_add = "", $check_code = NULL, $parameters_array = array()) : Action
    {

        $parameters = array(new DataParameter("item_id", $this->bean->key()));

        return new Action($title, "?cmd={$this->cmd}&$href_add", array_merge($parameters, $parameters_array), $check_code);

    }

    protected function processImpl()
    {

        $field_name = DBConnections::Get()->escape($this->field_name);

        $update_row = array();
        $update_row[$field_name] = $this->status;

        $this->bean->update($this->item_id, $update_row);

    }

}

?>