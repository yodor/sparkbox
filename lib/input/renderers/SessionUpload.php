<?php
include_once("input/renderers/ArrayField.php");
include_once("components/renderers/IPhotoRenderer.php");

abstract class SessionUpload extends ArrayField
{
    /**
     * @var UploadControlResponder
     */
    protected $ajax_handler;

    /**
     * SessionUpload constructor.
     * @param DataInput $input
     * @param UploadControlResponder $ajax_handler
     */
    public function __construct(DataInput $input, UploadControlResponder $ajax_handler)
    {

        $this->input = $input;

        $this->assignUploadHandler($ajax_handler);

        parent::__construct($this);

    }

    public function assignUploadHandler(UploadControlResponder $handler)
    {
        $this->ajax_handler = $handler;
        $this->setAttribute("handler_command", $this->ajax_handler->getCommand());
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/SessionUpload.css";
        return $arr;
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/jqplugins/jquery.form.js";
        $arr[] = SPARK_LOCAL . "/js/SessionUpload.js";
        return $arr;
    }

    protected function processInputAttributes()
    {
        parent::processInputAttributes();

        $transact_max = $this->input->getProcessor()->getTransactBeanItemLimit();
        $max_slots = 1;
        if ($transact_max>0) {
            $max_slots = $transact_max;
        }
        $this->setInputAttribute("max_slots", $max_slots);


        //TODO: set accepts attribute

        $this->setInputAttribute("type", "file");
    }

    public function renderDetails()
    {


        echo "<div class='Details'>";

        echo "<div class='Limits'>";

        echo "<div field='max_size'><label>UPLOAD_MAX_FILESIZE: </label><span>" . file_size(UPLOAD_MAX_FILESIZE) . "</span></div>";
        echo "<div field='max_post_size'><label>POST_MAX_FILESIZE: </label><span>" . file_size(POST_MAX_FILESIZE) . "</span></div>";
        echo "<div field='memory_limit'><label>MEMORY_LIMIT: </label><span>" . file_size(MEMORY_LIMIT) . "</span></div>";
        $transact_max = $this->input->getProcessor()->getTransactBeanItemLimit();
        $max_slots = 1;
        if ($transact_max>0) {
                $max_slots = $transact_max;
        }
        if ($this instanceof SessionFile) {
            $validator = $this->input->getValidator()->getItemValidator();
            if ($validator instanceof UploadDataValidator) {

                echo "<div field='accept_mimes'><label>Accept MIMEs: </label><span>" . implode(";",$validator->getAcceptMimes()) . "</span></div>";
            }
        }
        echo "<div field='max_slots'><label>Available Slots: </label><span>" . $max_slots . "</span></div>";


        echo "</div>";

        echo "</div>";
    }

    public function renderControls()
    {
        echo "<div class='Controls' >";

        echo "<div class='Buttons'>";

        $attr = $this->prepareInputAttributes();
        echo "<input $attr>";

        ColorButton::RenderButton("Browse", "", "browse");

        echo "</div>"; //Buttons

        echo "<div class='Progress'>";
        echo "<div class='bar'></div>";
        echo "<div class='percent'>0%</div>";
        echo "</div>"; //Progress

        echo "</div>"; //Controls

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

        //$validator = $this->ajax_handler->validator();

        echo "<div class='ArrayContents' field='" . $this->input->getName() . "'>";

        if (!is_array($images)) {
            $images = array($this->input->getValue());
        }

        foreach ($images as $idx => $storage_object) {

            if (is_null($storage_object)) continue;

            if (!($storage_object instanceof StorageObject)) continue;

            //$validator->processObject($storage_object);

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
                upload_control.setField("<?php echo $this->input->getName();?>");
                upload_control.initialize();

            });
        </script>
        <?php
        echo "</div>";

    }

}

?>