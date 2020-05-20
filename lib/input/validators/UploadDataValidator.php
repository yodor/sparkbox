<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

abstract class UploadDataValidator implements IInputValidator
{
    protected $maxsize = -1;

    //fill with mime strings to accept only these mimes to be uploaded
    protected $accept_mimes = array();

    public $skip_is_uploaded_check = FALSE;

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

    public static function errString(int $err, int $maxsize = 0)
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
                $ret = "The uploaded file exceeds the form limit of $maxsize";
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
            throw new Exception(UploadDataValidator::errString(UPLOAD_ERR_FORM_SIZE, file_size($this->maxsize)));
        }

        //UploadDataInputProcessor always create one FileStroageObject with error_status UPLOAD_ERR_NO_FILE
        $file_storage = $input->getValue();

        $upload_status = $file_storage->getUploadStatus();

        debug("StorageObject class: " . get_class($file_storage) . " | Upload status: $upload_status - " . UploadDataValidator::errString($upload_status));

        if ($upload_status === UPLOAD_ERR_NO_FILE) {
            if ($input->isRequired()) {
                if (!$input->getForm() || $input->getForm()->getEditID() < 1) {
                    throw new Exception(UploadDataValidator::errString($upload_status));
                }
            }
            return;
        }

        if ($upload_status !== UPLOAD_ERR_OK) {
            throw new Exception(tr("Upload error:") . "<br> " . UploadDataValidator::errString($upload_status, $this->maxsize));
        }

        if ($this->skip_is_uploaded_check) {
            //
            debug("skip_is_uploaded_check is TRUE");
        }
        else {
            if (!is_uploaded_file($file_storage->getTempName())) {
                debug("This is not an uploaded file: ".$file_storage->getTempName());
                throw new Exception(tr("Not an uploaded file"));
            }
        }

        if ($file_storage->getLength() > $this->maxsize) {
            // if the file is not less than the maximum allowed, print an error
            debug("Upload data lenght exceeds the maximum allowed");
            throw new Exception(tr("Uploaded file exceeds the maxmimum allowed size") . "<BR>" . "Max data size: " . file_size($this->maxsize));
        }

        if (count($this->accept_mimes)>0) {
            debug("Accepting mimes: ", $this->accept_mimes);
            debug("Uploaded mime: ".$file_storage->getMIME());
            if (!in_array($file_storage->getMIME(), $this->accept_mimes)) {
                throw new Exception(tr("Wrong mime type: ") . $file_storage->getMIME() . "<Br>".tr("Accepted mimes: ") . implode(';', $this->accept_mimes));
            }
        }


        $this->processUploadData($input);

    }

    abstract protected function processUploadData(DataInput $field);

    //   abstract protected function getStorageObject();

}

?>
