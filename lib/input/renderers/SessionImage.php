<?php
include_once("lib/input/renderers/SessionUpload.php");
include_once("lib/handlers/ImageUploadAjaxHandler.php");

class SessionImage extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new ImageUploadAjaxHandler());

        //TODO: not needed
        $this->setFieldAttribute("validator", "image");

    }

}

?>