<?php
include_once("lib/components/Component.php");
include_once("lib/beans/DBTableBean.php");
include_once("lib/forms/InputForm.php");

include_once("lib/forms/processors/FormProcessor.php");
include_once("lib/forms/renderers/FormRenderer.php");
include_once("lib/db/DBTransactor.php");

include_once("lib/handlers/UploadControlAjaxHandler.php");

include_once("lib/handlers/IRequestProcessor.php");

include_once("lib/panels/BeanTranslationDialog.php");

class InputFormView extends Component implements IDBTableEditor
{

    public $item_updated_message = "Information was updated";
    public $item_added_message = "Information was added";

    /**
     * @var DBTableBean|null
     */
    protected $bean = NULL;

    /**
     * @var InputForm|null
     */
    protected $form = NULL;

    protected $editID = -1;

    protected $form_renderer = NULL;
    protected $processor = NULL;
    protected $transactor = NULL;

    protected $error = false;

    public $reload_request = true;

    //transfer to this URL on processing finished
    public $reload_url = "";


    public function __construct(DBTableBean $bean = NULL, InputForm $form = NULL)
    {

        parent::__construct();


        $this->bean = $bean;
        $this->form = $form;


        $this->form_render = new FormRenderer();

        if ($this->form) {
            $this->form_render->setForm($form);
        }

        if ($this->bean instanceof DBTableBean) {
            $this->attributes["bean"] = get_class($this->bean);
            $this->form_render->setName(get_class($this->bean));
        }

        $this->processor = new FormProcessor();


        $this->transactor = new DBTransactor();

        $fieldNames = $this->form->getFieldNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getField($fieldName);
            $renderer = $field->getRenderer();

            if ($renderer instanceof MCETextArea) {

                $handler = $renderer->getImageBrowser()->getHandler();

                $handler->setSection(get_class($this->form), $field_name);
                $handler->setOwnerID(AdminPageLib::Instance()->getUserID());

            }
            //            else if ($renderer instanceof SessionImageField) {
            //                $uach = new ImageUploadAjaxHandler();
            //
            //                RequestController::addAjaxHandler($uach);
            //                $renderer->assignUploadHandler($uach);
            //            }
            //            else if ($renderer instanceof SessionFileField) {
            //                $uach = new FileUploadAjaxHandler();
            //
            //                RequestController::addAjaxHandler($uach);
            //                $renderer->assignUploadHandler($uach);
            //            }

        }


        if (TRANSLATOR_ENABLED) {
            $this->bean_translator = new BeanTranslationDialog();

        }

        $this->setEditID(-1);
    }


    public function getEditID() : int
    {
        return $this->editID;
    }

    public function setEditID(int $editID)
    {
        $this->editID = (int)$editID;
        $this->attributes["editID"] = $this->editID;

    }

    public function getBean() : DBTableBean
    {
        return $this->bean;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function getProcessor()
    {
        return $this->processor;
    }

    public function setProcessor(IFormProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getTransactor()
    {
        return $this->transactor;
    }

    public function setTransactor(IDBTransactor $transactor)
    {
        $this->transactor = $transactor;
    }

    public function processInput()
    {
        debug("InputFormView::processInput: start Processing AjaxHandlers First ");

        RequestController::processAjaxHandlers();

        debug("-------------------------------------");
        debug("InputFormView::processInput: start ");

        //will process external editID only if editID is not set
        if ($this->editID < 1 && isset($_GET["editID"])) {
            $this->setEditID((int)$_GET["editID"]);

            debug("InputFormView::processInput: Using editID='{$this->editID}' from GET ");
        }


        try {

            $this->form->loadBeanData($this->editID, $this->bean);

            $this->processor->processForm($this->form);

            $process_status = $this->processor->getStatus();
            debug("InputFormView::processInput: FormProcessor returned status => " . (int)$process_status);

            //form is ok transact to db using the bean
            if ($process_status === IFormProcessor::STATUS_OK) {

                $this->transactor->transactValues($this->form);

                $this->transactor->processBean($this->bean, $this->editID);

                //reload after adding item?
                if ($this->editID < 1) {
                    Session::SetAlert($this->item_added_message);


                }
                else {
                    Session::SetAlert($this->item_updated_message);
                }

                if ($this->reload_request === true) {
                    // 			  Session::set("replace_history", 1);
                    debug("InputFormView::processInput: finished redirection following ...");
                    debug("-------------------------------------");

                    //TODO: remove reload requirement here? session upload files might transact to dbrows changing UID of storage objects
                    if ($this->reload_url) {
                        header("Location: " . $this->reload_url);
                    }
                    else {
                        $page = HTMLPage::Instance();

                        $back_action = $page->getAction("back");
                        if (!is_null($back_action)) {
                            header("Location: " . $back_action->getHrefClean());
                        }
                        else {
                            header("Location: " . $_SERVER["REQUEST_URI"]);
                        }
                    }

                    exit;
                }

                debug("InputFormView::processInput: Successfull ");


            }
            else if ($process_status === IFormProcessor::STATUS_ERROR) {
                throw new Exception($this->processor->getMessage());
            }

        }
        catch (Exception $e) {
            debug("InputFormView::processInput: Execption received: " . $e->getMessage());
            Session::SetAlert($e->getMessage());
            $this->error = $e->getMessage();

        }

        debug("InputFormView::processInput: finished");
        debug("-------------------------------------");
    }

    public function startRender()
    {

        $attrs = $this->prepareAttributes();
        echo "<div $attrs>";

        if (strlen($this->caption) > 0) {
            echo "<div class='caption'>";
            echo $this->caption;
            echo "</div>";
        }

        $this->form_render->startRender();

    }

    public function renderImpl()
    {
        $this->form_render->renderImpl();
    }

    public function finishRender()
    {

        $this->form_render->renderSubmitLine($this->form);

        $this->form_render->finishRender();


    }


}

?>
