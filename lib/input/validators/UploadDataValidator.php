<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

abstract class UploadDataValidator implements IInputValidator
{
    protected int $maxsize = -1;

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
    public function setAcceptMimes(array $accept_mimes)
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
                $ret = "The uploaded file exceeds the init limit of " . UPLOAD_MAX_FILESIZE;
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $ret = "The uploaded file exceeds the form limit of ".file_size($maxsize);
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
    public function validate(DataInput $input)
    {
        debug("Using input: '{$input->getName()}'");

        if ($this->maxsize < 1) {
            $this->maxsize = UPLOAD_MAX_FILESIZE;
        }
        if ($this->maxsize > UPLOAD_MAX_FILESIZE) {
            $this->maxsize = UPLOAD_MAX_FILESIZE;
        }

        debug("Max data size: " . $this->maxsize);

        $content_length = 0;
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $content_length = $_SERVER['CONTENT_LENGTH'];
        }

        debug("Upload max data size: " . $this->maxsize . " | Content length: " . $content_length);

        //$_FILES array is always empty if post size > maxsize so check additionally here to be able to give correct error message
        if ($content_length > $this->maxsize) {
            throw new Exception(UploadDataValidator::errString(UPLOAD_ERR_FORM_SIZE, $this->maxsize));
        }

        //UploadDataInput processor always create default empty FileStorageObject
        $object = $input->getValue();

        if (! ($object instanceof StorageObject)) {
            throw new Exception("Value not instance of StorageObject");
        }

        debug("StorageObject class: " . get_class($object));

        if ($object->buffer()->length()<1 || empty($object->UID())) {
            debug("FileStorageObject is empty ...");
            if ($input->isRequired()) {
                if (!$input->getForm() || $input->getForm()->getEditID() < 1) {
                    throw new Exception("No file uploaded");
                }
            }
            return;
        }

        if ($object->buffer()->length() > $this->maxsize) {
            // if the file is not less than the maximum allowed, print an error
            debug("Upload data size exceeds the maximum allowed");
            throw new Exception(tr("Uploaded file size exceeds the maximum allowed size") . "<BR>" . "Max data size: " . file_size($this->maxsize));
        }

        if (count($this->accept_mimes)>0) {
            debug("Accepting mime types: ", $this->accept_mimes);
            debug("Uploaded mime type: ".$object->buffer()->mime());
            if (!in_array($object->buffer()->mime(), $this->accept_mimes)) {
                debug("Wrong mime type ...");
                throw new Exception(tr("Wrong mime type: ") . $object->buffer()->mime() . "<Br>".tr("Accepted mime types: ") . implode(';', $this->accept_mimes));
            }
        }
        else {
            debug("Accepting all mime types");
        }

    }


    abstract public function processObject(StorageObject $object) : void;


}

?>
