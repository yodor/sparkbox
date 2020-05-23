<?php

abstract class RequestResponder
{
    protected $cmd = NULL;

    protected $cancel_url = "";
    protected $success_url = "";

    protected $need_confirm = FALSE;

    protected $need_redirect = TRUE;

    const KEY_COMMAND = "cmd";
    const KEY_CONFIRM = "confirm_handler";

    public function __construct(string $cmd)
    {
        $this->cmd = $cmd;

        RequestController::Add($this);
    }

    public function setNeedConfirm(bool $mode)
    {
        $this->need_confirm = $mode;
    }

    public function getCommand(): string
    {
        return $this->cmd;
    }

    public function setCancelUrl(string $url)
    {
        $this->cancel_url = $url;
    }

    public function getCancelUrl(): string
    {
        return $this->cancel_url;
    }

    public function setSuccessUrl(string $url)
    {
        $this->success_url = $url;
    }

    public function getSuccessUrl(): string
    {
        return $this->success_url;
    }

    public function needProcess(): bool
    {
        return strcmp_isset(RequestResponder::KEY_COMMAND, $this->cmd, $_REQUEST);
    }

    public function processInput()
    {

        $process_error = FALSE;
        $redirectURL = "";

        $this->parseParams();

        if ($this instanceof JSONResponder) {
            //should always exit script or throw error
            $this->processImpl();
            return;
        }

        try {

            $redirectURL = $this->getCancelUrl();
            debug("need_redirect: " . (int)$this->need_redirect);

            if ($this->need_confirm) {
                if (!isset($_POST[RequestResponder::KEY_CONFIRM])) {
                    debug("Responder needs additional confirmation");
                    $this->processConfirmation();
                    return;
                }
                else {
                    debug("Responder is confirmed");
                }
            }

            $this->processImpl();
            $redirectURL = $this->getSuccessUrl();
        }
        catch (Exception $ex) {

            Session::SetAlert($ex->getMessage());
            debug("processImpl error: " . $ex->getMessage());
            $process_error = TRUE;

        }

        if ($this->need_redirect || $process_error) {
            if ($redirectURL) {
                debug("Redirecting to URL: $redirectURL");
                header("Location: " . $redirectURL);
                exit;
            }
            else {
                debug("Redirect URL is empty");
            }
        }

    }

    public function createAction($title = FALSE, $href = FALSE, $check_code = NULL, $data_parameters = array())
    {
        return NULL;
    }

    abstract protected function processImpl();

    abstract protected function parseParams();

    protected function processConfirmation()
    {
        $this->drawConfirmDialog();
    }

    public function drawConfirmDialog($title = "Confirm Action", $text = "Confirm action?")
    {
        $md = new ConfirmMessageDialog($title, "msg_confirm");

        ob_start();
        echo $text;
        echo "<form id=confirm_handler method=post>";
        echo "<input type=hidden name=confirm_handler value=1>";
        echo "</form>";
        $md->setContents(ob_get_contents());
        ob_end_clean();

        $md->render();

        ?>
        <script type='text/javascript'>
            let confirm_delete = new MessageDialog("msg_confirm");
            confirm_delete.buttonAction = function (action) {
                if (action == "confirm") {
                    console.log("Confirm");
                    var frm = document.getElementById("confirm_handler");
                    frm.submit();
                } else if (action == "cancel") {
                    console.log("Cancel");
                    document.location.replace("<?php echo $this->cancel_url;?>");
                }
            };

            onPageLoad(function () {
                confirm_delete.show();
            });

        </script>

        <?php

        unset($_GET["cmd"]);
    }
}

?>
