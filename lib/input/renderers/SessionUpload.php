<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/input/renderers/IArrayFieldRenderer.php");

//TODO: lead out as AjaxInputRenderer
abstract class SessionUpload extends InputField implements IArrayFieldRenderer
{
    protected $ajax_handler = NULL;

    /**
     * SessionUploadField constructor.
     * Register the handler with the RequestController
     * set the type attribute to 'file'
     * @param UploadControlAjaxHandler $ajax_handler
     */
    public function __construct(UploadControlAjaxHandler $ajax_handler)
    {
        parent::__construct();
        $this->setFieldAttribute("type", "file");

        $this->ajax_handler = $ajax_handler;

        RequestController::addAjaxHandler($this->ajax_handler);
    }

    public function assignUploadHandler(UploadControlAjaxHandler $handler)
    {
        $this->ajax_handler = $handler;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/SessionUpload.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/jqplugins/jquery.form.js";
        $arr[] = SITE_ROOT . "lib/js/SessionUpload.js";
        return $arr;
    }

    abstract public function renderArrayContents();

    public function renderControls()
    {
        echo "<div class='Controls' >";
        StyledButton::DefaultButton()->renderButton("Browse", "", "browse");

        $attr = $this->prepareFieldAttributes();

        echo "<input $attr>";

        echo "<div class='progress'>";
        echo "<div class='bar'></div>";
        echo "<div class='percent'>0%</div>";
        echo "</div>";
        echo "</div>";

    }

    public function renderElementSource()
    {
        //
    }

    public function startRender()
    {
        $max_slots = $this->field->getProcessor()->max_slots;
        $this->setFieldAttribute("max_slots", $max_slots);

        if ($this->ajax_handler instanceof UploadControlAjaxHandler) {
            $this->setAttribute("handler_command", $this->ajax_handler->getCommandName());
        }
        else {
            $this->setAttribute("handler_command", "null");
        }

        parent::startRender();
    }

    public function renderDetails()
    {
        $max_slots = $this->field->getProcessor()->max_slots;

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
        echo "<div field='max_slots'><label>Available Slots: </label><span>" . $max_slots . "</span></div>";

        echo "</div>";

        echo "</div>";
    }

    public function renderImpl()
    {


        echo "<div class='FieldElements'>";


        $this->renderDetails();
        echo "\n";
        $this->renderControls();
        echo "\n";
        $this->renderArrayContents();
        echo "\n";
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var upload_control = new SessionUpload();
                upload_control.attachWith("<?php echo $this->field->getName();?>");

            });
        </script>
        <?php
        echo "</div>";


    }

}

?>