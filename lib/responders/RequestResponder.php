<?php
include_once("utils/url/URL.php");

abstract class RequestResponder implements IGETConsumer
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

    public function getParameterNames(): array
    {
        return array("cmd");
    }

    public function getCommand(): string
    {
        return $this->cmd;
    }

    /**
     * Set the url to be redirected on error or cancel processing of this responder
     * Default is to use cleaned up version of the current url by removing all parameter names returned from getParameterNames
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
     * Default is to use cleaned up version of the current url by removing all parameter names returned from getParameterNames
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

    /**
     * @return void
     * @throws Exception
     */
    abstract protected function parseParams() : void;

    /**
     * @return void
     * @throws Exception
     */
    abstract protected function processImpl() : void;


    /**
     * Sets up the redirect url and checks for confirmation
     * if success_url or cancel_url are unset use the redirect url as default values
     * Calls parseParams and processImpl
     * @return void
     * @throws Exception
     */
    public function process() : void
    {

        //setup redirect url
        debug("Building redirect URL - removing parameter names: ", $this->getParameterNames());
        foreach ($this->getParameterNames() as $parameterName) {
            $this->redirect->remove($parameterName);
        }

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

    public function createAction(string $title = "") : ?Action
    {
        $url = URL::Current();
        $url->add(new URLParameter("cmd", $this->cmd));
        $action = new Action($title);
        $action->setURL($url);
        return $action;
    }

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
