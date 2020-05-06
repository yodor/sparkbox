<?php
include_once("handlers/RequestHandler.php");


class ChangePositionRequestHandler extends RequestHandler
{

    protected $item_id = -1;
    protected $bean = NULL;
    protected $type = "";

    protected $position = -1;

    protected $supported_content = array();

    public function __construct(DBTableBean $bean)
    {
        parent::__construct("reposition");

        if ($bean instanceof OrderedDataBean) {
            $this->supported_content = array("first", "last", "previous", "next", "fixed");
        }
        else if ($bean instanceof NestedSetBean) {
            $this->supported_content = array("left", "right");
        }
        else {
            throw new Exception("Hanlder requires OrderedDataBean or NestedSetBean source");
        }

        $this->bean = $bean;
    }

    protected function parseParams()
    {
        if (!isset($_GET["item_id"])) throw new Exception("Item ID not passed");
        $this->item_id = (int)$_GET["item_id"];

        if (!isset($_GET["type"])) throw new Exception("Position not passed");
        $type = $_GET["type"];

        if (!in_array($type, $this->supported_content)) throw new Exception("Position not supported");

        $this->type = $type;

        $arr = $_GET;
        unset($arr["cmd"]);
        unset($arr["item_id"]);
        unset($arr["type"]);
        if (isset($arr["position"])) {
            $this->position = (int)$arr["position"];
            unset($arr["position"]);
        }

        $this->cancel_url = queryString($arr);
        $this->cancel_url = $_SERVER['PHP_SELF'] . $this->cancel_url;

    }

    protected function process()
    {

        if ($this->bean instanceof NestedSetBean) {
            if (strcmp($this->type, "left") == 0) {
                $this->bean->moveLeft($this->item_id);

            }
            else if (strcmp($this->type, "right") == 0) {
                $this->bean->moveRight($this->item_id);
            }
        }

        else if ($this->bean instanceof OrderedDataBean) {

            if (strcmp($this->type, "first") == 0) {
                $this->bean->reorderTop($this->item_id);
            }
            else if (strcmp($this->type, "last") == 0) {
                $this->bean->reorderBottom($this->item_id);
            }
            else if (strcmp($this->type, "previous") == 0) {
                $this->bean->reorderUp($this->item_id);
            }
            else if (strcmp($this->type, "next") == 0) {
                $this->bean->reorderDown($this->item_id);
            }
            else if (strcmp($this->type, "fixed") == 0) {
                if ($this->position > 0) {
                    $this->bean->reorderFixed($this->item_id, $this->position);
                }
                else {
                    throw new Exception("Incorrect position value for fixed reposition type");
                }
            }

        }

    }

}

?>