<?php
include_once("responders/RequestResponder.php");

include_once("dialogs/ConfirmMessageDialog.php");

class ToggleFieldResponder extends RequestResponder
{

    protected int $item_id;
    protected DBTableBean $bean;
    protected int $status;
    protected string $field_name;

    public function __construct(DBTableBean $bean, $cmd = "toggle_field")
    {
        parent::__construct($cmd);

        $this->bean = $bean;

        $this->need_confirm = TRUE;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
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

    public function getParameterNames() : array
    {
        $result = parent::getParameterNames();
        $result[] = "item_id";
        $result[] = "status";
        $result[] = "field";
        return $result;
    }

    public function createAction(string $title = "Toggle") : ?Action
    {
        $action = parent::createAction($title);
        $action->getURL()->add(new DataParameter("item_id", $this->bean->key()));
        return $action;
    }

    protected function processImpl() : void
    {

        $field_name = DBConnections::Open()->escape($this->field_name);

        $update_row = array();
        $update_row[$field_name] = $this->status;

        $this->bean->update($this->item_id, $update_row);

    }

}

?>