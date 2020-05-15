<?php

class MCETextArea extends InputField
{

    protected static $image_browser = NULL;

    public function __construct(DataInput $input)
    {

        parent::__construct($input);

        //force single instance of the dialog to all MCETextAreas to prevent double session upload
        if (!self::$image_browser) {
            include_once("panels/MCEImageBrowserDialog.php");
            self::$image_browser = new MCEImageBrowserDialog();
        }

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/MCETextArea.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/MCETextArea.js";
        $arr[] = SPARK_LOCAL . "/js/tiny_mce/jquery.tinymce.min.js";
        return $arr;
    }

    public function setAttribute($name, $value)
    {
        $this->setInputAttribute($name, $value);
        self::$image_browser->setAttribute($name, $value);
    }

    public function getImageBrowser()
    {
        return self::$image_browser;

    }

    public function renderImpl()
    {

        $attrs = $this->prepareInputAttributes();

        echo "<textarea class='MCETextArea' $attrs>";

        $field_value = $this->input->getValue();

        $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");
        echo $field_value;

        echo "</textarea>";
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var mce = new MCETextArea();
                mce.attachWith("<?php echo $this->input->getName();?>");
            });
        </script>
        <?php

    }

    //    public function renderValueImpl()
    //    {
    //        $field_value = $this->input->getValue();
    //
    //        if (strlen($field_value) > 0) {
    //            $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");
    //            $field_value = str_replace("\n", "<BR>", $field_value);
    //            echo $field_value;
    //        }
    //        else {
    //            echo "-";
    //        }
    //    }

}

?>
