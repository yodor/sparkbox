<?php
include_once("input/renderers/SessionUpload.php");
include_once("handlers/FileUploadAjaxHandler.php");

class SessionFile extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new FileUploadAjaxHandler());

//        $this->setInputAttribute("validator", "file");
    }


}

?>