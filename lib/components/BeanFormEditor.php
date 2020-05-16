<?php
include_once("components/Component.php");
include_once("beans/DBTableBean.php");
include_once("forms/InputForm.php");

include_once("forms/processors/FormProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("db/BeanTransactor.php");

include_once("handlers/UploadControlAjaxHandler.php");

include_once("handlers/IRequestProcessor.php");

include_once("dialogs/BeanTranslationDialog.php");

class BeanFormEditor extends Container implements IBeanEditor
{

    public $item_updated_message = "Information was updated";
    public $item_added_message = "Information was added";

    /**
     * @var DBTableBean|null
     */
    protected $bean = NULL;

    protected $editID = -1;

    /**
     * @var InputForm|null
     */
    protected $form = NULL;

    protected $form_renderer = NULL;
    protected $processor = NULL;
    protected $transactor = NULL;

    protected $error = FALSE;

    public $reload_request = TRUE;

    //transfer to this URL on processing finished
    public $reload_url = "";

    public function __construct(DBTableBean $bean, InputForm $form)
    {

        parent::__construct();

        $this->bean = $bean;
        $this->form = $form;

        $this->form_render = new FormRenderer($form);

        $this->attributes["bean"] = get_class($this->bean);
        $this->form_render->setName(get_class($this->bean));

        $this->processor = new FormProcessor();

        $this->transactor = new BeanTransactor($this->bean, $this->editID);

        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $pos => $fieldName) {

            $field = $this->form->getInput($fieldName);
            $renderer = $field->getRenderer();

            if ($renderer instanceof MCETextArea) {

                $handler = $renderer->getImageBrowser()->getHandler();

                $handler->setSection(get_class($this->form), $fieldName);
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

        $this->append($this->form_render);

        //        if (Session::Get(SparkPage::IteratorSessionKey($this->bean->getTableName()))) {
        //            $action_back = new Action("", Session::Get(SparkPage::IteratorSessionKey($this->bean->getTableName())), array());
        //            $action_back->setAttribute("action", "back");
        //            $action_back->setAttribute("title", tr("Back"));
        //            AdminPageLib::Instance()->addAction($action_back);
        //        }

    }

    public function getEditID(): int
    {
        return $this->editID;
    }

    public function setEditID(int $editID)
    {
        $this->editID = (int)$editID;
        $this->attributes["editID"] = $this->editID;
        $this->transactor->setEditID($editID);

    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
        $this->transactor->setBean($bean);
    }

    public function getForm(): InputForm
    {
        return $this->form;
    }

    public function getProcessor(): IFormProcessor
    {
        return $this->processor;
    }

    public function setProcessor(IFormProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getTransactor(): BeanTransactor
    {
        return $this->transactor;
    }

    public function setTransactor(BeanTransactor $transactor)
    {
        $this->transactor = $transactor;
    }

    public function processInput()
    {
        debug("Processing AjaxHandlers ...");

        RequestController::processAjaxHandlers();

        //will process external editID only if editID is not set
        if ($this->editID < 1 && isset($_GET["editID"])) {
            $this->setEditID((int)$_GET["editID"]);

            debug("Using editID='{$this->editID}' from _GET ");
        }

        try {

            debug("Loading bean data into form");
            $this->form->loadBeanData($this->getEditID(), $this->getBean());

            debug("Calling form processor");
            $this->processor->process($this->form);

            $process_status = $this->processor->getStatus();
            debug("FormProcessor status => " . (int)$process_status);

            //form is ok transact to db using the bean
            if ($process_status === IFormProcessor::STATUS_OK) {

                debug("Transacting form values");
                $this->transactor->processForm($this->form);

                debug("Processing bean");
                $this->transactor->processBean();

                //reload after adding item?
                if ($this->editID < 1) {
                    Session::SetAlert($this->item_added_message);
                }
                else {
                    Session::SetAlert($this->item_updated_message);
                }

                if ($this->reload_request === TRUE) {
                    // 			  Session::set("replace_history", 1);
                    debug("Finished processing - redirect following");

                    //TODO: remove reload requirement here? session upload files might transact to dbrows changing UID of storage objects
                    if ($this->reload_url) {
                        header("Location: " . $this->reload_url);
                    }
                    else {
                        $page = HTMLPage::Instance();

                        $back_action = $page->getAction("back");
                        if ($back_action instanceof Action) {
                            header("Location: " . $back_action->getURLBuilder()->url());
                        }
                        else {
                            header("Location: " . $_SERVER["REQUEST_URI"]);
                        }
                    }

                    exit;
                }

                debug("Process status is successful");

            }
            else if ($process_status === IFormProcessor::STATUS_ERROR) {
                debug("Process status is error");
                throw new Exception($this->processor->getMessage());
            }

        }
        catch (Exception $e) {
            debug("Exception received: " . $e->getMessage());
            Session::SetAlert($e->getMessage());
            $this->error = $e->getMessage();

        }

        debug("Finished");

    }

}

?>
