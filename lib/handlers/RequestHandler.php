<?php
include_once("handlers/JSONResponse.php");
include_once("handlers/IRequestProcessor.php");

abstract class RequestHandler implements IRequestProcessor
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
        debug("Accepting command: '$cmd'");

    }

    public function setNeedConfirm(bool $mode)
    {
        $this->need_confirm = $mode;
    }

    public function getCommandName(): string
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

    public function shouldProcess(): bool
    {
        if (isset($_REQUEST[RequestHandler::KEY_COMMAND]) && strcmp($_REQUEST[RequestHandler::KEY_COMMAND], $this->cmd) == 0) {

            return TRUE;
        }
        return FALSE;
    }

    public function processHandler()
    {
        $this->parseParams();

        $do_process = FALSE;

        if ($this->need_confirm && !isset($_POST[RequestHandler::KEY_CONFIRM])) {

            $this->processConfirmation();
        }
        else {
            $do_process = TRUE;
        }

        if (!$do_process) return;

        debug("Calling process with need_redirect: " . (int)$this->need_redirect);

        $redirectURL = "";

        if ($this->process()) {
            debug("Process returned true");
            $redirectURL = $this->getSuccessUrl();
        }
        else {
            debug("Process returned false");
            $redirectURL = $this->getCancelUrl();
        }

        if ($this->need_redirect) {
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

    abstract protected function process();

    abstract protected function parseParams();

    protected function processConfirmation()
    {
        $this->drawConfirmDialog();
    }

    public function drawConfirmDialog($title = "Confirm Action", $text = "Confirm action?")
    {
        $md = new ConfirmMessageDialog($title, "msg_confirm");

        $btn_ok = $md->getButtons()->getByAction(MessageDialog::BUTTON_ACTION_CONFIRM);
        $btn_ok->setContents("Confirm");
        $btn_ok->setAttribute("onClick", "javascript:confirmHandler()");

        $btn_cancel = $md->getButtons()->getByAction(MessageDialog::BUTTON_ACTION_CANCEL);
        $btn_cancel->setContents("Cancel");
        $btn_cancel->setAttribute("onClick", "javascript:cancelHandler()");

        $md->startRender();

        echo "<form id=confirm_handler_form method=post>";

        echo $text;

        echo "<br>";

        echo "<input type=hidden name=confirm_handler value=1>";
        echo "</form>";
        ?>
        <script type='text/javascript'>
            function confirmHandler() {
                var frm = document.getElementById("confirm_handler_form");
                frm.submit();
            }

            function cancelHandler() {
                document.location.replace("<?php echo $this->cancel_url;?>");
            }

            onPageLoad(function () {
                showPopupPanel("msg_confirm");
            });
        </script>

        <?php

        $md->finishRender();

        unset($_GET["cmd"]);
    }
}

?>
