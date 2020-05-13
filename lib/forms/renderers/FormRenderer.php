<?php
include_once("components/Component.php");
include_once("components/InputComponent.php");
include_once("components/Container.php");

class FieldSet extends Component
{
    protected $tagName = "fieldset";

    protected $fields = array();

}

class FormRenderer extends Component
{
    protected $form;
    protected $submitButton;

    protected $render_field_callback = NULL;

    protected $tagName = "FORM";

    const FIELD_HBOX = "HBox";
    const FIELD_VBOX = "VBox";

    const SUBMIT_NAME = "SubmitForm";

    protected $layout = FormRenderer::FIELD_HBOX;

    protected $method;

    protected $submitLine;

    const METHOD_POST = "post";
    const METHOD_GET = "get";

    public function __construct(InputForm $form)
    {
        parent::__construct();

        $this->form = $form;
        $form->setRenderer($this);


        $this->setAttribute("enctype", "multipart/form-data");

        $this->setMethod(FormRenderer::METHOD_POST);

        $button = StyledButton::DefaultButton();
        $button->setAttribute("action", "submit");
        $button->setText("Submit");
        $button->setType(StyledButton::TYPE_SUBMIT);
        $button->setName(FormRenderer::SUBMIT_NAME);

        $this->submitButton = $button;

        $this->submitLine = new Container();
        $this->submitLine->setClassName("SubmitLine");

        $textSpace = new Container();
        $textSpace->setClassName("TextSpace");
        $this->submitLine->append($textSpace);

        $buttons = new Container();
        $buttons->setClassName("Buttons");

        $buttons->append($this->submitButton);
        $this->submitLine->append($buttons);

        $this->setLayout($this->layout);

    }
    public function setMethod(string $method)
    {
        $this->method = $method;
        $this->setAttribute("method", $method);
    }
    public function getMethod() : string
    {
        return $this->method;
    }
    public function getSubmitLine() : Container
    {
        return $this->submitLine;
    }

    public function getButtons() : Container
    {
        $buttons = $this->submitLine->getByClassName("Buttons");
        if ($buttons instanceof Container) return $buttons;

        throw new Exception("Buttons container not found");
    }
    public function getSubmitButton() : StyledButton
    {
        return $this->submitButton;
    }

    public function getTextSpace() : Container
    {
        $textSpace = $this->submitLine->getByClassName("TextSpace");
        if ($textSpace instanceof Container) return $textSpace;

        throw new Exception("TextSpace container not found");
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/FormRenderer.css";

        return $arr;
    }

    public function setLayout(string $mode)
    {
        $this->layout = $mode;
        $this->setAttribute("layout" , $this->layout);
    }

    public function setRenderFieldCallback($fname)
    {
        $this->render_field_callback = $fname;
    }

    public function setForm(InputForm $form)
    {
        $this->form = $form;
        $this->form->setRenderer($this);

    }

    public function startRender()
    {
        parent::startRender();
        if (strcmp($this->method, FormRenderer::METHOD_POST)==0) {
            //echo "<input type=hidden name='MAX_FILE_SIZE' value='" . UPLOAD_MAX_FILESIZE . "'>";
        }
    }

    protected function renderImpl()
    {
        $inputs = $this->form->getInputs();
        foreach ($inputs as $name => $input) {
            $this->renderInput($input);
        }

        $this->renderSubmitLine();

        //echo "<div class=clear></div>";

    }

    protected function processAttributes()
    {
        parent::processAttributes();
        $this->setName($this->form->getName());

    }


    public function renderSubmitLine()
    {
        $this->submitButton->setValue($this->form->getName());
        $this->submitLine->render();
    }

    public function renderInput(DataInput $input)
    {

        $callback_rendered = FALSE;
        if ($this->render_field_callback) {
            if (is_callable($this->render_field_callback)) {
                $callback_rendered = call_user_func($this->render_field_callback, $input, $this);
            }
            else {
                //TODO: Check if exception throwing is more appropriate here
                debug("callback set but callback render function not callable");

            }
        }
        if (!$callback_rendered) {
            $component = new InputComponent($input);
            $component->render();
        }

    }

}

?>
