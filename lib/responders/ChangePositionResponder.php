<?php
include_once("responders/RequestResponder.php");
include_once("dialogs/InputMessageDialog.php");

class ChangePositionResponder extends RequestResponder
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
            if ($this->position<1) {
                Session::SetAlert("Incorrect position specified");
            }
        }

        if ($this->bean instanceof OrderedDataBean) {
            if (strcmp($this->type, "fixed") == 0) {
                if ($this->position < 1) {
                    $this->need_redirect = FALSE;
                }
            }
        }

        $this->cancel_url = queryString($arr);
        $this->cancel_url = $_SERVER['PHP_SELF'] . $this->cancel_url;

        $this->success_url = $this->cancel_url;
    }

    protected function processImpl()
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

                    $dialog = new InputMessageDialog();
                    $dialog->getInput()->setName("position");
                    $dialog->getInput()->setLabel("Input new position");

                    ?>
                    <script type="text/javascript">
                        let input_position = new MessageDialog("<?php echo $dialog->getID();?>");
                        input_position.buttonAction = function (action) {
                            if (action == "confirm") {

                                let position = input_position.input.value;

                                var searchParams = new URLSearchParams(location.search);
                                searchParams.set("position", position);

                                window.location.href = `${location.pathname}?${searchParams}`;
                            } else if (action == "cancel") {
                                window.location.href = "<?php echo $this->cancel_url;?>";
                            }
                        }

                        onPageLoad(function () {
                            input_position.show();
                        });

                    </script>
                    <?php

                }
            }

        }

    }

}

?>