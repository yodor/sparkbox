<?php
include_once("lib/input/renderers/SessionUpload.php");
include_once("lib/handlers/FileUploadAjaxHandler.php");

class SessionFile extends SessionUpload
{

    public function __construct()
    {
        parent::__construct(new FileUploadAjaxHandler());

        $this->setFieldAttribute("validator", "file");
    }

    public function renderArrayContents()
    {

        $field_name = $this->field->getName();

        $objects = $this->field->getValue();

        if (!$this->ajax_handler) {
            echo "<div class='ArrayContents'>";
            echo "<div class='error'>Upload Handler not registered</div>";
            echo "</div>";
            return;
        }

        $validator = $this->ajax_handler->validator();

        echo "<div class='ArrayContents'>";

        foreach ($objects as $idx => $storage_object) {

            // 	  if(is_null($storage_object) || $storage_object->isPurged())continue;
            if (is_null($storage_object)) continue;

            $validator->processFile($storage_object);

            echo $this->ajax_handler->getHTML($storage_object, $field_name);
        }
        echo "</div>";
    }

}

?>