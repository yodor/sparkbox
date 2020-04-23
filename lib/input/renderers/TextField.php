<?php
include_once("lib/input/renderers/InputFieldTag.php");

class TextField extends InputFieldTag
{

    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("type", "text");

    }


}

?>