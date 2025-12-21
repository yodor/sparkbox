<?php
include_once("utils/url/URL.php");
include_once("components/PageScript.php");
include_once("dialogs/ConfirmMessageDialog.php");

class ConfirmResponderScript extends PageScript
{
    protected string $cancelURL;

    public function setCancelURL(string $cancelURL) : void
    {
        $this->cancelURL = $cancelURL;
    }

    public function code() : string
    {
        return <<<JS
        onPageLoad(function () {
            let confirm_dialog = new MessageDialog("ConfirmResponderDialog");
            
            confirm_dialog.buttonAction = function (action) {
                if (action == "confirm") {
                    //console.log("Confirm");
                    const form = confirm_dialog.element.querySelector("FORM");
                    form.submit();
                    
                } else if (action == "cancel") {
                    //console.log("Cancel");
                    document.location.replace("{$this->cancelURL}");
                }
            };
            confirm_dialog.show();
        });
JS;

    }
}
class ConfirmResponderDialog extends ConfirmMessageDialog
{
    public function __construct()
    {
        parent::__construct();

        $form = new Form();

        $form->setMethod(Form::METHOD_POST);

        $input = new Input("hidden", RequestResponder::KEY_CONFIRM, 1);
        $form->items()->append($input);

        $this->text->items()->append($form);

    }
}
abstract class RequestResponder extends SparkObject implements IGETConsumer
{

    protected string $confirm_dialog_title = "Confirm Action";
    protected string $confirm_dialog_text = "Confirm action?";

    protected string $cancel_url = "";
    protected string $success_url = "";

    protected bool $need_confirm = FALSE;
    protected bool $need_redirect = TRUE;

    //match JSONRequest.js KEY_COMMAND
    const string KEY_COMMAND = "responder";

    //
    const string KEY_CONFIRM = "confirm_request";

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

    public function __construct()
    {
        parent::__construct();

        //allow name override ex JSONComponentResponder
        if (!$this->getName()) {
            $this->setName(get_class($this));
        }

        $this->url = URL::Current();

        $this->redirect = URL::Current();

        RequestController::Add($this);
    }

    public function getParameterNames(): array
    {
        return array(RequestResponder::KEY_COMMAND);
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
        return strcmp_isset(RequestResponder::KEY_COMMAND, $this->getName(), $_REQUEST);
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
        $url->add(new URLParameter(RequestResponder::KEY_COMMAND, $this->getName()));
        $action = new Action($title);
        $action->setURL($url);
        return $action;
    }

    protected function processConfirmation() : void
    {
        //will be added as IPageComponent
        $md = new ConfirmResponderDialog();
        $md->setText($this->confirm_dialog_text);
        $md->setTitle($this->confirm_dialog_title);

        $script = new ConfirmResponderScript();
        $script->setCancelURL($this->cancel_url);
    }

    public function setConfirmDialogTitle(string $title) : void
    {
        $this->confirm_dialog_title = $title;
    }
    public function getConfirmDialogTitle() : string
    {
        return $this->confirm_dialog_title;
    }

    public function setConfirmDialogText(string $text) : void
    {
        $this->confirm_dialog_text = $text;
    }
    public function getConfirmDialogText() : string
    {
        return $this->confirm_dialog_text;
    }

}

?>
