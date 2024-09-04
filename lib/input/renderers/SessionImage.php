<?php
include_once("input/renderers/SessionUpload.php");
include_once("responders/json/ImageUploadResponder.php");

class SessionImage extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new ImageUploadResponder());

        $this->setInputAttribute("accept", implode(",", ImageScaler::SupportedMimes));

    }

}

?>
