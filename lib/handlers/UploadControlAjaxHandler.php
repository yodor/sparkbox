<?php
include_once("lib/handlers/JSONRequestHandler.php");
include_once("lib/input/renderers/InputField.php");


abstract class UploadControlAjaxHandler extends JSONRequestHandler
{

    public const PARAM_FIELD_NAME = "field_name";
    public const PARAM_UID = "uid";

    public const PARAM_CONTROL_NAME = "upload_control";

    //ajax handler is working with '$field_name' field
    protected $field_name = NULL;

    /**
     * UploadControlAjaxHandler constructor.
     *
     * @param string $cmd Having cmd=$cmd in the request makes this handler process the request
     */
    public function __construct(string $cmd)
    {
        parent::__construct($cmd);

    }

    /**
     * @throws Exception
     */
    protected function parseParams()
    {
        parent::parseParams();
        if (!isset($_GET[UploadControlAjaxHandler::PARAM_FIELD_NAME])) throw new Exception("Field name not passed");
        $field_name = $_GET[UploadControlAjaxHandler::PARAM_FIELD_NAME];
        $this->field_name = str_replace("[]", "", $field_name);

    }

    /**
     * Prepare html contents for the object that was posted via ajax
     * @param FileStorageObject $storageObject
     * @param string $field_name
     * @return string the html contents
     */
    abstract public function getHTML(FileStorageObject &$storageObject, string $field_name);

    /**
     * Create validator for this upload control
     * @return mixed IInputValidator
     */
    abstract public function validator();

    protected function _upload(JSONResponse $resp)
    {
        debug("UploadControlAjaxHandler::_upload() ...");

        debug("UploadControlAjaxHandler::_upload() creating input validator");
        $validator = $this->validator();

        //virtual input field to process ajax posted data
        $input = new DataInput($this->field_name, "Upload Control", 1);

        $input->setValidator($validator);
        $input->setProcessor(new UploadDataInputProcessor());

        debug("UploadControlAjaxHandler::_upload() loading POST data");
        $input->loadPostData($_POST);

        debug("UploadControlAjaxHandler::_upload() validating input data");
        $input->validate();

        //FileStorageObject
        $uploadObject = $input->getValue();

        //TODO:multiple uploaded files can be processed?
        $num_files = 0;

        if ($input->haveError()) {
            throw new Exception("There was error processing file <B>" . $uploadObject->getFileName() . "</b> Error: " . $input->getError());
        }

        $this->assignUploadObjects($resp, $uploadObject);

        debug("UploadControlAjaxHandler::_upload() finished");
    }

    protected function assignUploadObjects(JSONResponse $resp, FileStorageObject $uploadObject)
    {
        debug("UploadControlAjaxHandler::assignUploadObjects() ...");

        $html = $this->getHTML($uploadObject, $this->field_name);
        //
        $jsonObject = array("name" => $uploadObject->getFilename(), "uid" => $uploadObject->getUID(), "mime" => $uploadObject->getMIME(), "html" => $html,);

        //JSONResponse returns all dynamically assigned properties in its result
        $resp->objects[] = $jsonObject;

        //prepare the original data for storing into the session
        $fileData = new FileStorageObject();
        $fileData->setUploadStatus(UPLOAD_ERR_OK);

        //do not set tempName as it is valid only during current request
        //$file_storage->setTempName($upload_object->getTempName());
        $fileData->setTimestamp($uploadObject->getTimestamp());
        $fileData->setUID($uploadObject->getUID());

        //assign original contents of the uploaded file. so it can be accessed during final form submit - not in this ajax context
        $fileData->setData(file_get_contents($uploadObject->getTempName()));
        $fileData->setFilename($uploadObject->getFileName());
        $fileData->setMIME($uploadObject->getMIME());

        //store the original data in the session array by the field name and UID
        $_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][(string)$uploadObject->getUID()] = serialize($fileData);
        debug("UploadControlAjaxHandler::assignUploadObjects() | Session storing file UID: " . $uploadObject->getUID() . " for field['" . $this->field_name . "']");

        //       $num_files++;

        //JSONResponse.response() returns dynamically assigned properties in its result
        $resp->object_count = 1;
    }

    protected function _remove(JSONResponse $resp)
    {
        debug("UploadControlAjaxHandler::_remove() ...");

        if (!isset($_GET[UploadControlAjaxHandler::PARAM_UID])) throw new Exception("UID not passed");

        $uid = (string)$_GET[UploadControlAjaxHandler::PARAM_UID];

        if (strlen($uid) > 50) throw new Exception("UID maximum size reached");

        debug("UploadControlAjaxHandler::_remove() UID: " . $uid);

        if (isset($_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][$uid])) {

            debug("UploadControlAjaxHandler::_remove() Removing UID:'$uid' from session array");
            unset($_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][$uid]);

        }

        debug("UploadControlAjaxHandler::_remove() finished");
    }

}

?>
