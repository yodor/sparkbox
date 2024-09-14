<?php
include_once("utils/url/URL.php");

abstract class RequestResponder
{
    protected string $cmd = "";

    protected string $cancel_url = "";
    protected string $success_url = "";

    protected bool $need_confirm = FALSE;
    protected bool $need_redirect = TRUE;

    const string KEY_COMMAND = "cmd";
    const string KEY_CONFIRM = "confirm_handler";

    /**
     * Current request URL
     *
     * @var URL
     */
    protected URL $url;

    /**
     * Cleaned up from commands used to redirect on success or failure/cancel if $success/$cancel urls are not set
     * @var URL
     */
    protected URL $redirect;

    public function __construct(string $cmd)
    {
        $this->cmd = $cmd;
        $this->url = URL::Current();

        $this->redirect = URL::Current();

        RequestController::Add($this);
    }


    /**
     * Clear the request url from the parameters of this responder
     * Default remove the cmd, subclasses remove their parameters
     */
    protected function buildRedirectURL() : void
    {
        $this->redirect->remove("cmd");
    }

    public function getCommand(): string
    {
        return $this->cmd;
    }

    /**
     * Set the url to be redirected on error or cancel processing of this responder
     *
     * @param string $url
     * @return void
     */
    public function setCancelUrl(string $url) : void
    {
        $this->cancel_url = $url;
    }

    public function getCancelUrl(): string
    {
        return $this->cancel_url;
    }

    /**
     * Set the url to be redirected after successfully processing this responder
     *
     * @param string $url
     * @return void
     */
    public function setSuccessUrl(string $url) : void
    {
        $this->success_url = $url;
    }

    public function getSuccessUrl(): string
    {
        return $this->success_url;
    }

    public function needRedirect() : bool
    {
        return $this->need_redirect;
    }

    /**
     * Return true if this responder want to process this request based on the command present inside the $_REQUEST
     * @return bool
     */
    public function accept(): bool
    {
        return strcmp_isset(RequestResponder::KEY_COMMAND, $this->cmd, $_REQUEST);
    }


    public function process() : void
    {

        //setup redirect url
        $this->buildRedirectURL();

        if (!$this->cancel_url) {
            $this->cancel_url = $this->redirect->toString();
        }
        if (!$this->success_url) {
            $this->success_url = $this->redirect->toString();
        }

        $this->parseParams();

        if ($this instanceof JSONResponder) {
            $this->processImpl();
            return;
        }

        debug("Needs confirmation: ".(($this->need_confirm)?"YES":"NO"));

        if ($this->need_confirm) {
            if (!isset($_POST[RequestResponder::KEY_CONFIRM])) {
                debug("Asking confirmation");
                //disable redirection
                $this->need_redirect = false;
                //construct the dialog so it can be attached to the page_components
                $this->processConfirmation();
                return;
            }
            else {
                debug("Confirmation received");
            }
        }

        $this->processImpl();

    }

    public function createAction($title = FALSE, $href = FALSE, $check_code = NULL, $data_parameters = array())
    {
        return NULL;
    }

    abstract protected function processImpl();

    /**
     * @return void
     * @throws Exception
     */
    abstract protected function parseParams() : void;

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
