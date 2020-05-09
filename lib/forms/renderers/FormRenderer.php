<?php
include_once("components/Component.php");
include_once("components/InputComponent.php");

class FieldSet extends Component
{
    protected $fields = array();

    public function startRender()
    {
        echo "<fieldset>";
    }

    public function finishRender()
    {
        echo "</fieldset>";
    }

    public function renderImpl()
    {

    }
}

class FormRenderer extends Component
{
    protected $form = NULL;
    protected $submit_button = NULL;
    protected $render_field_callback = NULL;

    const FIELD_HBOX = "HBox";
    const FIELD_VBOX = "VBox";

    protected $field_layout = FormRenderer::FIELD_VBOX;

    protected $buttons = array();

    public $contains_upload = false;

    protected $field_renderer = NULL;

    public function __construct($field_layout = FormRenderer::FIELD_HBOX)
    {
        parent::__construct();
        $this->attributes["method"] = "post";
        $this->attributes["enctype"] = "multipart/form-data";


        $this->submit_button = StyledButton::DefaultButton();
        $this->submit_button->setAttribute("action", "form_submit");
        $this->submit_button->setName("submit_item");
        $this->submit_button->setText("Submit Form");
        $this->submit_button->setValue("submit");

        $this->submit_button->setType(StyledButton::TYPE_SUBMIT);


        $this->setLayout($field_layout);

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/FormRenderer.css";

        return $arr;
    }

    public function addButton(StyledButton $b)
    {
        $this->buttons[$b->getText()] = $b;
    }

    public function getButton($text)
    {
        return $this->buttons[$text];
    }

    public function setLayout($mode)
    {
        $this->field_layout = $mode;
        $this->attributes["field_layout"] = $this->field_layout;
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
        $attrs = $this->prepareAttributes();
        echo "<form $attrs>";
        if ($this->contains_upload) {
            echo "<input type=hidden name='MAX_FILE_SIZE' value='" . UPLOAD_MAX_FILESIZE . "'>";
        }


    }

    public function finishRender()
    {


        echo "</form>";
    }

    public function renderImpl()
    {
        $inputs = $this->form->getInputs();
        foreach ($inputs as $name => $input) {
            $this->renderInput($input);
        }
    }

    public function getSubmitName(InputForm $form)
    {
        return $this->submit_button->getName();
    }

    public function getSubmitButton()
    {
        return $this->submit_button;
    }

    public function renderForm(InputForm $form)
    {
        $this->form = $form;

        $this->startRender();

        $this->renderImpl();

        $this->renderSubmitLine($this->form);

        echo "<div class=clear></div>";

        $this->finishRender();

    }

    public function renderSubmitLine(InputForm $form)
    {
        echo "<div class='SubmitLine'>";

        echo "<div class='TextSpace'>";
        echo "</div>";

        echo "<div class='Buttons'>";


        foreach ($this->buttons as $href => $btn) {
            $btn->render();

        }

        $this->submit_button->render();


        echo "</div>";

        echo "</div>";
    }


    public function renderInput(DataInput $input)
    {

        $callback_rendered = false;
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
