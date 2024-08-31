<?php
include_once("responders/RequestResponder.php");
include_once("dialogs/InputMessageDialog.php");

class ChangePositionResponder extends RequestResponder
{

    protected int $item_id = -1;
    protected DBTableBean $bean;
    protected string $type = "";

    protected int $position = -1;

    protected array $supported_content = array();

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
            throw new Exception("Responder requires OrderedDataBean or NestedSetBean");
        }

        $this->bean = $bean;

        //define early so it can be catched in the page rendering
        $dialog = new InputMessageDialog();
        $dialog->getInput()->setName("position");
        $dialog->getInput()->setLabel("Input new position");

    }

    protected function parseParams()
    {
        if (!$this->url->contains("item_id")) {
            throw new Exception("Item ID not passed");
        }

        $this->item_id = (int)$this->url->get("item_id")->value();

        if (!$this->url->contains("type")) {
            throw new Exception("Position not passed");
        }

        $type = $this->url->get("type")->value();

        if (!in_array($type, $this->supported_content)) throw new Exception("Type not supported");

        $this->type = $type;

        if ($this->url->contains("position")) {
            $this->position = (int)$this->url->get("position")->value();

            if ($this->position < 1) {
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
    }

    protected function buildRedirectURL()
    {
        parent::buildRedirectURL();

        $this->url->remove("item_id");
        $this->url->remove("type");
        $this->url->remove("position");
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

                    ?>
                    <script type="text/javascript">

                        onPageLoad(function () {

                            let input_position = new InputMessageDialog();

                            input_position.buttonAction = function (action) {
                                if (action == "confirm") {

                                    let position = input_position.input().val();

                                    let url = new URL(window.location.href);
                                    url.searchParams.set("position", position);

                                    window.location.href = url.href;

                                } else if (action == "cancel") {

                                    window.location.href = "<?php echo $this->cancel_url;?>";
                                }
                            }

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
