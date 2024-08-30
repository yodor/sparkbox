<?php
include_once("responders/json/JSONResponder.php");
include_once("storage/FileStorageObject.php");

abstract class UploadControlResponder extends JSONResponder
{

    public const PARAM_FIELD_NAME = "field_name";
    public const PARAM_UID = "uid";

    public const PARAM_CONTROL_NAME = "upload_control";

    //ajax handler is working with '$field_name' field
    protected $field_name = NULL;

    /**
     * UploadControlResponder constructor.
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
        if (!isset($_GET[UploadControlResponder::PARAM_FIELD_NAME])) throw new Exception("Field name not passed");
        $field_name = $_GET[UploadControlResponder::PARAM_FIELD_NAME];
        $this->field_name = str_replace("[]", "", $field_name);

    }

    /**
     * Prepare html contents for the object that was posted via ajax
     * @param FileStorageObject $storageObject
     * @param string $field_name
     * @return string the html contents
     */
    abstract public function getHTML(StorageObject $object, string $field_name) : string;

    /**
     * Create validator for this upload control
     * @return mixed IInputValidator
     */
    abstract public function validator() : UploadDataValidator;

    protected function _upload(JSONResponse $resp)
    {

        debug("Creating temporary DataInput object ... ");
        $validator = $this->validator();

        //virtual input field to process ajax posted data
        $input = new DataInput($this->field_name, "Upload Control", 1);

        $input->setValidator($validator);
        new UploadDataInput($input);

        debug("Loading input processor with _POST data");
        $input->getProcessor()->loadPostData($_POST);

        debug("Validating ...");
        $input->validate();

        //FileStorageObject
        $uploadObject = $input->getValue();

        //TODO:multiple uploaded files can be processed?
        $num_files = 0;

        if ($input->haveError()) {
            throw new Exception(tr("Error").": ".$input->getError());
        }

        $this->assignUploadObjects($resp, $uploadObject);

        debug("Finished");
    }

    protected function assignUploadObjects(JSONResponse $resp, FileStorageObject $uploadObject)
    {
        debug("...");

        $html = $this->getHTML($uploadObject, $this->field_name);
        //
        $jsonObject = array("name" => $uploadObject->getFilename(), "uid" => $uploadObject->UID(),
                            "mime" => $uploadObject->buffer()->mime(), "html" => $html,);

        //JSONResponse returns all dynamically assigned properties in its result
        $resp->objects[] = $jsonObject;

        //store the original data in the session array by the field name and UID
        $_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][$uploadObject->UID()] = serialize($uploadObject);
        debug("Stored FileStorageObject to session using UID: " . $uploadObject->UID() . " for field['" . $this->field_name . "']");

        //JSONResponse.response() returns dynamically assigned properties in its result
        $resp->object_count = 1;
    }

    protected function _remove(JSONResponse $resp)
    {
        debug("...");

        if (!isset($_GET[UploadControlResponder::PARAM_UID])) throw new Exception("UID not passed");

        $uid = (string)$_GET[UploadControlResponder::PARAM_UID];

        if (strlen($uid) > 50) throw new Exception("UID maximum size reached");

        debug("Using UID: " . $uid);

        if (isset($_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][$uid])) {

            debug("Removing UID:'$uid' from session array");
            unset($_SESSION[self::PARAM_CONTROL_NAME][$this->field_name][$uid]);

        }

        debug("Finished");
    }

}

?>
