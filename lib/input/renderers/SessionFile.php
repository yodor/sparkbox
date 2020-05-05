<?php
include_once("lib/input/renderers/SessionUpload.php");
include_once("lib/handlers/FileUploadAjaxHandler.php");

class SessionFile extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new FileUploadAjaxHandler());

        $this->setFieldAttribute("validator", "file");
    }


}

?>