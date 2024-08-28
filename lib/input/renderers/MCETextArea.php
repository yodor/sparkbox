<?php

class MCETextArea extends InputField
{

    protected static $image_browser = NULL;
    protected $tagName = "TEXTAREA";

    public function __construct(DataInput $input)
    {

        parent::__construct($input);

        //force single instance of the dialog to all MCETextAreas to prevent double session upload
        if (!MCETextArea::$image_browser) {
            include_once("dialogs/MCEImageBrowserDialog.php");
            MCETextArea::$image_browser = new MCEImageBrowserDialog();
        }

    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/MCETextArea.css";
        return $arr;
    }

    public function requiredScript(): array
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

    public function getImageBrowser(): MCEImageBrowserDialog
    {
        return self::$image_browser;
    }

    public function startRender()
    {
        $this->contents = htmlentities(mysql_real_unescape_string($this->input->getValue()), ENT_QUOTES, "UTF-8");
        parent::startRender();
    }

    public function render()
    {
        parent::render();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var mce = new MCETextArea();
                mce.setName("<?php echo $this->input->getName();?>");
                mce.initialize();
            });
        </script>
        <?php
    }

    protected function prepareAttributes()
    {
        $ret = parent::prepareAttributes();
        $ret.= parent::prepareInputAttributes();
        return $ret;
    }
}

?>
