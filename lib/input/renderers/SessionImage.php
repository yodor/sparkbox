<?php
include_once("lib/input/renderers/SessionUpload.php");
include_once("lib/handlers/ImageUploadAjaxHandler.php");

class SessionImage extends SessionUpload
{


    public function __construct()
    {
        parent::__construct(new ImageUploadAjaxHandler());

        //TODO: not needed
        $this->setFieldAttribute("validator", "image");

    }


    public function renderArrayContents()
    {

        $field_name = $this->field->getName();

        $images = $this->field->getValue();


        if (!$this->ajax_handler) {
            echo "<div class='ArrayContents'>";
            echo "<div class='error'>Upload Handler not registered</div>";
            echo "</div>";
            return;
        }

        $validator = $this->ajax_handler->validator();


        echo "<div class='ArrayContents'>";

        foreach ($images as $idx => $storage_object) {

            if (is_null($storage_object)) continue;

            $validator->processImage($storage_object);

            echo $this->ajax_handler->getHTML($storage_object, $field_name);

        }
        echo "</div>";
    }


}

?>