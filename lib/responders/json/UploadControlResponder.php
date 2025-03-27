<?php
include_once("responders/json/JSONResponder.php");
include_once("storage/FileStorageObject.php");
include_once("utils/SessionData.php");

abstract class UploadControlResponder extends JSONResponder
{

    //json request required parameter names
    public const PARAM_FIELD_NAME = "field_name";
    public const PARAM_UID = "uid";

    //ajax handler is working with '$field_name' field
    protected string $field_name = "";

    protected SessionData $data;

    /**
     * UploadControlResponder constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        parent::parseParams();
        if (!isset($_GET[UploadControlResponder::PARAM_FIELD_NAME])) throw new Exception("Field name not passed");
        $field_name = $_GET[UploadControlResponder::PARAM_FIELD_NAME];
        $this->field_name = str_replace("[]", "", $field_name);

        $this->data = new SessionData(SessionData::Prefix($this->field_name,SessionData::UPLOAD_CONTROL));
    }

    /**
     * Prepare html contents for the object that was posted via ajax
     * @param StorageObject $object
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
        $input = new ArrayDataInput($this->field_name, "Upload Control", 1);

        //validator will be called for each element of the ArrayDataInput
        $input->setValidator($validator);

        //set processor to the input
        new UploadDataInput($input);

        debug("Loading input processor with _POST data");
        $input->getProcessor()->loadPostData($_POST);

        debug("Validating ...");
        $input->validate();

        //FileStorageObject
        $value = $input->getValue();

        if (is_array($value)) {
            debug("Processing multiple uploeded files: ".count($value));
            foreach ($value as $idx=>$uploadObject) {
                $error = $input->getErrorAt($idx);
                if ($error) {
                    debug("Element[$idx]: Validation error: $error");
                }
                else {
                    $this->assignUploadObject($resp, $uploadObject);
                }
            }
        }
        else {

            if ($input->haveError()) {
                throw new Exception(tr("Error").": ".$input->getError());
            }

            $this->assignUploadObject($resp, $value);
        }

        debug("Finished");
    }

    /**
     * Assign result to JSONResponse
     * 1. calls createResponseObject and assign to the object field of $resp
     * 2. storeToSession the $uploadObject
     * 3. increment object_count field of $resp
     *
     * @param JSONResponse $resp
     * @param FileStorageObject $uploadObject
     * @return void
     */
    private function assignUploadObject(JSONResponse $resp, FileStorageObject $uploadObject)
    {
        debug("...");

        //!Do store first
        //StorageObject can change UID depending on strage type used
        $this->storeUploadObject($uploadObject);

        //prepare representation for this storage object
        $html = $this->getHTML($uploadObject, $this->field_name);

        //JSONResponse returns all dynamically assigned properties in its result
        //create response array with metadata and html
        $resp->objects[] = $this->createResponseObject($uploadObject, $html);

        //JSONResponse.response() returns dynamically assigned properties in its result
        $resp->object_count = count($resp->objects);
    }


    /**
     * Store upload object
     * Default is to use the StorageObject into session using the StorageObject UID
     * @param FileStorageObject $uploadObject
     * @return void
     */
    protected function storeUploadObject(FileStorageObject $uploadObject) : void
    {
        //store the original data in the session array by the field name and UID
        $this->data->set($uploadObject->UID(),$uploadObject);
        debug("Stored FileStorageObject to session data using UID: " . $uploadObject->UID() . " for field['" . $this->field_name . "']");
    }

    /**
     *  Create array using FileStorageObject meta-data and html to use as representation in the upload control
     *  Data user name, uid, mime
     *  HTML is prepared from getHTML
     * @param FileStorageObject $uploadObject
     * @param string $html
     * @return array
     */
    protected function createResponseObject(FileStorageObject $uploadObject, string $html) : array
    {
        return array(
            "name" => $uploadObject->getFilename(),
            "uid" => $uploadObject->UID(),
            "mime" => $uploadObject->buffer()->mime(),
            "html" => $html);
    }

    protected function _remove(JSONResponse $resp)
    {
        debug("...");

        if (!isset($_GET[UploadControlResponder::PARAM_UID])) throw new Exception("UID not passed");

        $uid = (string)$_GET[UploadControlResponder::PARAM_UID];

        if (strlen($uid) > 50) throw new Exception("UID maximum size reached");

        debug("Removing UID[$uid] from session data");

        $this->data->remove($uid);

        debug("Finished");
    }

}

?>
