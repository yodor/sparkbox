<?php
include_once("components/LabelSpan.php");
include_once("input/renderers/ArrayField.php");
include_once("components/renderers/IPhotoRenderer.php");

abstract class SessionUpload extends InputField
{

    protected ?UploadControlResponder $responder;


    public function __construct(DataInput $dataInput, UploadControlResponder $responder)
    {

        parent::__construct($dataInput);

        $this->responder = $responder;

        $this->addClassName("SessionUpload");

        $this->input = new Input();
        $this->input->setType("file");
        //allow uploading multiple files at once

        $this->input->setAttribute("multiple", "");

        $field_elements = new Container();
        $field_elements->setComponentClass("FieldElements");

        $details = $this->createDetails();
        $field_elements->items()->append($details);

        $controls = new ClosureComponent($this->createControls(...), false);
        $controls->setComponentClass("");
        $field_elements->items()->append($controls);

        $arrayContents = new ClosureComponent($this->createArrayContents(...), false);
        $arrayContents->setComponentClass("");
        $field_elements->items()->append($arrayContents);

        $this->items()->append($field_elements);
    }

    public function setResponder(UploadControlResponder $responder) : void
    {
        $this->responder = $responder;

    }

    public function getResponder() : UploadControlResponder
    {
        return $this->responder;
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
        $arr[] = SPARK_LOCAL . "/js/SessionUpload.js";
        return $arr;
    }

    //SessionUpload is ArrayDataInput
    protected function processAttributes() : void
    {


        parent::processAttributes();

        $this->input->setName($this->dataInput->getName()."[]");

        $this->setAttribute("handler_command", $this->responder->getCommand());

        $limit = $this->dataInput->getProcessor()->getTransactBeanItemLimit();
        $uploadLimit = 1;
        if ($limit>0) {
            $uploadLimit = $limit;
        }
        $this->input->setAttribute("max_slots", $uploadLimit);

        $validator = $this->dataInput->getValidator();

        if ($validator instanceof ArrayInputValidator) {
            $itemValidator = $validator->getItemValidator();
            if ($itemValidator instanceof UploadDataValidator) {
                $this->input->setAttribute("accept", implode(",", $itemValidator->getAcceptMimes()));
            }
        }

        $max_slots = $this->items()
            ->getByContainerClass("FieldElements")?->items()
            ->getByContainerClass("Details")?->items()
            ->getByContainerClass("Limits")?->items()
            ->getByAttribute("max_slots", "field");
        if ($max_slots instanceof LabelSpan) {
            $max_slots->span()->setContents($uploadLimit);
        }
    }

    public function createDetails() : Container
    {
        $details =  new Container(false);
        $details->setComponentClass("Details");

        $limits = new Container(false);
        $limits->setComponentClass("Limits");
        $details->items()->append($limits);

        $max_size = new LabelSpan("UPLOAD_MAX_SIZE: ", file_size(UPLOAD_MAX_SIZE));
        $max_size->setAttribute("field", "max_size");
        $limits->items()->append($max_size);

        $memory_limit = new LabelSpan("MEMORY_LIMIT: ", file_size(MEMORY_LIMIT));
        $memory_limit->setAttribute("field", "memory_limit");
        $limits->items()->append($memory_limit);

        $accept_mimes = new LabelSpan("Accept MIMEs: ", $this->input->getAttribute("accept"));
        $accept_mimes->setAttribute("field", "accept_mimes");
        $limits->items()->append($accept_mimes);

        $max_slots = new LabelSpan("Available Slots: ", -1);
        $max_slots->setAttribute("field", "max_slots");
        $limits->items()->append($max_slots);

        return $details;
    }

    protected function createControls() : void
    {
        echo "<div class='Controls' >";

            echo "<div class='Buttons'>";

            $this->input->render();

            ColorButton::RenderButton("Browse", "", "browse");

            echo "</div>"; //Buttons

            echo "<div class='Progress'>";
            echo "<div class='bar'></div>";
            echo "<div class='percent'>0%</div>";
            echo "</div>"; //Progress

        echo "</div>"; //Controls

    }

    protected function createArrayContents() : void
    {
        $field_name = $this->dataInput->getName();

        $images = $this->dataInput->getValue();

        if (!$this->responder) {
            echo "<div class='ArrayContents'>";
            echo "<div class='error'>Upload Handler not registered</div>";
            echo "</div>";
            return;
        }

        echo "<div class='ArrayContents' field='{$this->dataInput->getName()}'>";

        if (!is_array($images)) {
            $images = array($this->dataInput->getValue());
        }

        foreach ($images as $idx => $storage_object) {

            if (is_null($storage_object)) continue;

            if (!($storage_object instanceof StorageObject)) continue;

            echo $this->responder->getHTML($storage_object, $field_name);

        }
        echo "</div>";
    }

    public function finishRender()
    {
        parent::finishRender(); // TODO: Change the autogenerated stub
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let upload_control = new SessionUpload();
                upload_control.setField("<?php echo $this->dataInput->getName();?>");
                upload_control.initialize();

            });
        </script>
        <?php
    }

}

?>
