<?php
//do not include circular references from MCEImageBrowserDialog

class MCETextArea extends TextArea
{

    protected static ?MCEImageBrowserDialog $image_browser = null;

    public function __construct(DataInput $input)
    {
        //force single instance of the dialog to all MCETextAreas to prevent double session upload
        if (is_null(MCETextArea::$image_browser)) {
            include_once("dialogs/MCEImageBrowserDialog.php");
            MCETextArea::$image_browser = new MCEImageBrowserDialog();
        }

        parent::__construct($input);

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

    public function getImageBrowser(): MCEImageBrowserDialog
    {
        return self::$image_browser;
    }

    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let mce = new MCETextArea();
                mce.setName("<?php echo $this->dataInput->getName();?>");
                mce.initialize();
            });
        </script>
        <?php
    }

}

?>
