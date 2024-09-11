<?php
include_once("utils/url/URL.php");

abstract class RequestResponder
{
    protected string $cmd = "";

    protected string $cancel_url = "";
    protected string $success_url = "";

    protected bool $need_confirm = FALSE;

    protected bool $need_redirect = TRUE;

    const KEY_COMMAND = "cmd";
    const KEY_CONFIRM = "confirm_handler";

    /**
     * Current URL
     * @var URL
     */
    protected URL $url;

    public function __construct(string $cmd)
    {
        $this->cmd = $cmd;

        RequestController::Add($this);
        $this->url = URL::Current();
    }

    public function __destruct()
    {
        RequestController::Remove($this);
    }
    /**
     * Clear the request url from the parameters of this responder
     */
    protected function buildRedirectURL()
    {
        $this->url->remove("cmd");
    }

    public function setNeedConfirm(bool $mode)
    {
        $this->need_confirm = $mode;
    }

    public function getCommand(): string
    {
        return $this->cmd;
    }

    public function setCancelUrl(string $url) : void
    {
        $this->cancel_url = $url;
    }

    public function getCancelUrl(): string
    {
        return $this->cancel_url;
    }

    public function setSuccessUrl(string $url) : void
    {
        $this->success_url = $url;
    }

    public function getSuccessUrl(): string
    {
        return $this->success_url;
    }

    /**
     * Return true if this responder want to process this request based on the command present inside the $_REQUEST
     * @return bool
     */
    public function accept(): bool
    {
        return strcmp_isset(RequestResponder::KEY_COMMAND, $this->cmd, $_REQUEST);
    }

    public function process()
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

            //redirect URL is already set?
            $redirectURL = $this->getCancelUrl();
            if (!$redirectURL) {
                $this->buildRedirectURL();
                $redirectURL = $this->url->toString();
                $this->cancel_url = $redirectURL;
            }
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

            //success URL is set - use it for redirection
            if ($this->getSuccessUrl()) {
                $redirectURL = $this->getSuccessUrl();
            }

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

    protected function processConfirmation() : void
    {
        $this->setupConfirmDialog();
    }

    protected function setupConfirmDialog(string $title = "Confirm Action", string $text = "Confirm action?") : void
    {
        //will be added as IPageComponent
        $md = new ConfirmMessageDialog($title, "msg_confirm");

        $md->buffer()->start();
        echo $text;
        echo "<form method=post>";
        echo "<input type=hidden name=confirm_handler value=1>";
        echo "</form>";
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let confirm_delete = new MessageDialog();
                confirm_delete.setID("msg_confirm");
                confirm_delete.buttonAction = function (action) {
                    if (action == "confirm") {
                        //console.log("Confirm");
                        var frm = $(confirm_delete.visibleSelector()+" FORM");
                        frm.submit();
                    } else if (action == "cancel") {
                        //console.log("Cancel");
                        document.location.replace("<?php echo $this->cancel_url;?>");
                    }
                };
                confirm_delete.show();
            });
        </script>
        <?php
        $md->buffer()->end();

    }
}

?>
