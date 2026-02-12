<?php
include_once("input/renderers/SessionUpload.php");
include_once("responders/json/FileUploadResponder.php");

class SessionFile extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new FileUploadResponder());

    }

}