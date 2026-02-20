<?php
include_once("input/renderers/SessionUpload.php");
include_once("responders/json/ChunkedFileUploadResponder.php");

class SessionChunkedFile extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new ChunkedFileUploadResponder());

    }

}