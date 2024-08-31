<?php
include_once("input/renderers/InputField.php");

abstract class PlainUpload extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setInputAttribute("type", "file");
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
        $storage_object = $this->input->getValue();
        $field_name = $this->input->getName();

        echo "<div class='FieldElements'>";

        echo "<div class='Details'>";

        echo "<div class='Limits'>";
        echo "<div field='max_size'><label>UPLOAD_MAX_FILESIZE: </label><span>" . file_size(UPLOAD_MAX_FILESIZE) . "</span></div>";
        echo "<div field='max_post_size'><label>POST_MAX_FILESIZE: </label><span>" . file_size(POST_MAX_FILESIZE) . "</span></div>";
        echo "<div field='memory_limit'><label>MEMORY_LIMIT: </label><span>" . file_size(MEMORY_LIMIT) . "</span></div>";
        echo "</div>";

        echo "<span class='Filename'>";
        echo "</span>";

        echo "</div>";

        echo "<div class='Controls' >";
        ColorButton::RenderButton("Browse", "", "browse");

        $attr = $this->prepareInputAttributes();

        echo "<input $attr>";

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
                upload_field.attachWith("<?php echo $this->input->getName();?>");

            });
        </script>
        <?php

    }

}

?>
