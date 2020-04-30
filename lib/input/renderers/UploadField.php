<?php
include_once("lib/input/renderers/InputField.php");


abstract class PlainUpload extends InputField
{


    public function __construct()
    {
        parent::__construct();
        $this->setFieldAttribute("type", "file");

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/PlainUpload.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/PlainUpload.js";
        return $arr;
    }

    protected abstract function renderContents(StorageObject $storage_object);


    public function renderImpl()
    {
        $storage_object = $this->field->getValue();
        $field_name = $this->field->getName();

        echo "<div class='FieldElements'>";


        echo "<div class='Details'>";

        if (strlen($this->caption) > 0) {
            echo "<span class='Caption'>";
            echo $this->caption;
            echo "</span>";
        }

        echo "<div class='Limits'>";
        echo "<div field='max_size'><label>UPLOAD_MAX_FILESIZE: </label><span>" . file_size(UPLOAD_MAX_FILESIZE) . "</span></div>";
        echo "<div field='max_post_size'><label>POST_MAX_FILESIZE: </label><span>" . file_size(POST_MAX_FILESIZE) . "</span></div>";
        echo "<div field='memory_limit'><label>MEMORY_LIMIT: </label><span>" . file_size(MEMORY_LIMIT) . "</span></div>";
        echo "</div>";


        echo "<span class='Filename'>";
        echo "</span>";

        echo "</div>";

        echo "<div class='Controls' >";
        StyledButton::DefaultButton()->renderButton("Browse", "", "browse");

        $attr = $this->prepareFieldAttributes();

        echo "<input $attr>";

        echo "</div>";

        echo "<div class='Slots'><div class='Contents'>";

        if ($storage_object && $storage_object instanceof StorageObject) {
            $this->renderContents($storage_object);
        }

        echo "</div></div>";

        echo "</div>";

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let upload_field = new PlainUpload();
                upload_field.attachWith("<?php echo $this->field->getName();?>");

            });
        </script>
        <?php

    }

}

?>
