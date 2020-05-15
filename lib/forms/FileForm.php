<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

class FileForm extends InputForm
{

    public $accept_mimes = NULL;

    public function __construct($accept_mimes = FALSE)
    {
        parent::__construct();

        $field = new DataInput("data", "File", 1);
        $field->setValidator(new FileUploadValidator());
        new UploadDataInput($field);
        new FileField($field);
        $this->addInput($field);

        $field = new DataInput("caption", "Caption", 0);
        new TextField($field);
        $this->addInput($field);

        if (!$accept_mimes) {
            $this->accept_mimes = array("application/acrobat", "application/x-pdf", "application/pdf",
                                        "application/rtf", "application/msword", "application/msexcel",
                                        "application/vnd.oasis.opendocument.text",
                                        "application/vnd.oasis.opendocument.spreadsheet",
                                        "application/vnd.oasis.opendocument.presentation",
                                        "application/vnd.oasis.opendocument.graphics", "application/vnd.ms-excel",
                                        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                        "application/vnd.ms-powerpoint",
                                        "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",

            );
        }

        $this->getInput("data")->getValidator()->setAcceptMimes($this->accept_mimes);

    }

}

?>