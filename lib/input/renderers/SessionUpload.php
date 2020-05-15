<?php
include_once("input/renderers/ArrayField.php");
include_once("components/renderers/IPhotoRenderer.php");

//TODO: lead out as AjaxInputRenderer
abstract class SessionUpload extends InputField
{
    protected $ajax_handler = NULL;

    /**
     * SessionUploadField constructor.
     * Register the handler with the RequestController
     * set the type attribute to 'file'
     * @param UploadControlAjaxHandler $ajax_handler
     */
    public function __construct(DataInput $input, UploadControlAjaxHandler $ajax_handler)
    {
        parent::__construct($input);

        $this->input = $input;

        $this->ajax_handler = $ajax_handler;

        RequestController::addAjaxHandler($this->ajax_handler);

        $this->setAttribute("handler_command", $this->ajax_handler->getCommandName());

    }

    public function assignUploadHandler(UploadControlAjaxHandler $handler)
    {
        $this->ajax_handler = $handler;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/SessionUpload.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/jqplugins/jquery.form.js";
        $arr[] = SPARK_LOCAL . "/js/SessionUpload.js";
        return $arr;
    }

    protected function processInputAttributes()
    {
        parent::processInputAttributes();

        $max_slots = $this->input->getProcessor()->max_slots;

        $this->setInputAttribute("type", "file");
        $this->setInputAttribute("max_slots", $max_slots);

    }

    public function renderDetails()
    {
        $max_slots = $this->input->getProcessor()->max_slots;

        echo "<div class='Details'>";

        echo "<div class='Limits'>";

        echo "<div field='max_size'><label>UPLOAD_MAX_FILESIZE: </label><span>" . file_size(UPLOAD_MAX_FILESIZE) . "</span></div>";
        echo "<div field='max_post_size'><label>POST_MAX_FILESIZE: </label><span>" . file_size(POST_MAX_FILESIZE) . "</span></div>";
        echo "<div field='memory_limit'><label>MEMORY_LIMIT: </label><span>" . file_size(MEMORY_LIMIT) . "</span></div>";
        echo "<div field='max_slots'><label>Available Slots: </label><span>" . $max_slots . "</span></div>";

        echo "</div>";

        echo "</div>";
    }

    public function renderControls()
    {
        echo "<div class='Controls' >";
        ColorButton::RenderButton("Browse", "", "browse");

        $attr = $this->prepareInputAttributes();

        echo "<input $attr>";

        echo "<div class='progress'>";
        echo "<div class='bar'></div>";
        echo "<div class='percent'>0%</div>";
        echo "</div>";
        echo "</div>";

    }

    public function renderArrayContents()
    {
        $field_name = $this->input->getName();

        $images = $this->input->getValue();

        if (!$this->ajax_handler) {
            echo "<div class='ArrayContents'>";
            echo "<div class='error'>Upload Handler not registered</div>";
            echo "</div>";
            return;
        }

        $validator = $this->ajax_handler->validator();

        echo "<div class='ArrayContents' field='" . $this->input->getName() . "'>";

        foreach ($images as $idx => $storage_object) {

            if (is_null($storage_object)) continue;

            $validator->process($storage_object);

            echo $this->ajax_handler->getHTML($storage_object, $field_name);

        }
        echo "</div>";
    }

    public function renderImpl()
    {

        echo "<div class='FieldElements'>";

        $this->renderDetails();

        $this->renderControls();

        $this->renderArrayContents();

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var upload_control = new SessionUpload();
                upload_control.attachWith("<?php echo $this->input->getName();?>");

            });
        </script>
        <?php
        echo "</div>";

    }

}

?>
