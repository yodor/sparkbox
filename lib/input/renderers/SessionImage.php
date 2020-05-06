<?php
include_once("input/renderers/SessionUpload.php");
include_once("handlers/ImageUploadAjaxHandler.php");

class SessionImage extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new ImageUploadAjaxHandler());

    }

}

?>