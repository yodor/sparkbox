<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

abstract class UploadDataValidator implements IInputValidator
{

    //fill with mime strings to accept only these mimes to be uploaded
    protected array $accept_mimes = array();

    //public bool $skip_is_uploaded_check = FALSE;

    //in ArrayInputField case Processors get called only once, but validators are called for each array value
    public function __construct()
    {


    }

    /**
     * @param array $accept_mimes
     */
    public function setAcceptMimes(array $accept_mimes) : void
    {
        $this->accept_mimes = $accept_mimes;
    }

    public function getAcceptMimes() : array
    {
        return $this->accept_mimes;
    }

    public static function errString(int $err, int $maxsize = 0) : string
    {
        $ret = "Undefined error: $err";
        switch ($err) {
            case UPLOAD_ERR_OK:
                $ret = "There is no error, the file uploaded with success.";
                break;
            case UPLOAD_ERR_INI_SIZE:
                $ret = "The uploaded file exceeds the init limit of " . Spark::ByteLabel(Spark::Get(Config::UPLOAD_MAX_SIZE));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $ret = "The uploaded file exceeds the form limit of ".Spark::ByteLabel($maxsize);
                break;
            case UPLOAD_ERR_PARTIAL:
                $ret = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $ret = "No file was selected for upload";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $ret = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $ret = "Failed to write file to disk";
                break;
        }
        return $ret;
    }

    /**
     * Check uploaded size is within the set limits.
     * Check the mime is from accepted mimes
     * @param DataInput $input
     * @throws Exception
     */
    public function validate(DataInput $input) : void
    {
        Debug::ErrorLog("Using input: '{$input->getName()}'");



        $content_length = 0;
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $content_length = $_SERVER['CONTENT_LENGTH'];
        }

        Debug::ErrorLog("Content length: " . $content_length);

        //UploadDataInput processor always create default empty FileStorageObject
        $object = $input->getValue();

        if (! ($object instanceof StorageObject)) {
            throw new Exception("Value not instance of StorageObject");
        }

        Debug::ErrorLog("StorageObject class: " . get_class($object));

        if ($object->buffer()->length()<1 || empty($object->UID())) {
            Debug::ErrorLog("FileStorageObject is empty ...");
            if ($input->isRequired()) {
                if (!$input->getForm() || $input->getForm()->getEditID() < 1) {
                    throw new Exception("No file uploaded");
                }
            }
            return;
        }

        if (count($this->accept_mimes)>0) {
            Debug::ErrorLog("Accepting mime types: ", $this->accept_mimes);
            Debug::ErrorLog("Uploaded mime type: ".$object->buffer()->mime());
            if (!in_array($object->buffer()->mime(), $this->accept_mimes)) {
                Debug::ErrorLog("Wrong mime type ...");
                throw new Exception(tr("Wrong mime type: ") . $object->buffer()->mime() . "<Br>".tr("Accepted mime types: ") . implode(';', $this->accept_mimes));
            }
        }
        else {
            Debug::ErrorLog("Accepting all mime types");
        }

    }


    abstract public function processObject(StorageObject $object) : void;


}

?>
