<?php
include_once("input/renderers/InputField.php");

abstract class PlainUpload extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->input->setType("file");
        $this->addClassName("PlainUpload");
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/PlainUpload.css";
        return $arr;
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/PlainUpload.js";
        return $arr;
    }

    protected abstract function renderContents(StorageObject $object) : void;

    protected function renderImpl()
    {
        $storage_object = $this->dataInput->getValue();
        $field_name = $this->dataInput->getName();

        echo "<div class='FieldElements'>";

        echo "<div class='Details'>";

        echo "<div class='Limits'>";
        echo "<div field='max_size'><label>UPLOAD_MAX_SIZE: </label><span>" . file_size(UPLOAD_MAX_SIZE) . "</span></div>";
        echo "<div field='memory_limit'><label>MEMORY_LIMIT: </label><span>" . file_size(MEMORY_LIMIT) . "</span></div>";
        echo "</div>";

        echo "<span class='Filename'>";
        echo "</span>";

        echo "</div>";

        echo "<div class='Controls' >";
        Button::TextButton("Browse", "browse")->render();

        parent::renderImpl();

        echo "</div>";

        echo "<div class='Slots'><div class='Contents'>";

        if ($storage_object instanceof StorageObject) {
            $this->renderContents($storage_object);
        }

        echo "</div></div>";

        echo "</div>";

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let upload_field = new PlainUpload();
                upload_field.attachWith("<?php echo $this->dataInput->getName();?>");

            });
        </script>
        <?php

    }

}

?>
