<?php
include_once("components/Form.php");
include_once("components/InputComponent.php");


class InputGroupRenderer extends Container
{

    protected InputGroup $group;

    public function __construct(InputGroup $group)
    {
        parent::__construct(false);
        $this->tagName = "fieldset";
        $this->group = $group;
        $this->setName($group->getName());

        $legend = new Component(false);
        $legend->setTagName("legend");
        $legend->setContents($this->group->getDescription());
        $this->items()->append($legend);
    }

}

class FormRenderer extends Form
{
    protected InputForm $form;

    const string LAYOUT_HBOX = "HBox";
    const string LAYOUT_VBOX = "VBox";
    protected string $layout = FormRenderer::LAYOUT_VBOX;


    /**
     * Submit element name attribute default value.
     * FormProcessor look for array key with this name in the request data during processing matching its value to the form name
     * By default InputForm name is equal to the PHP class name unless overwritten using setName method
     */
    const string SUBMIT_NAME = "SubmitForm";


    /**
     * Default submit button
     * @var Button
     */
    protected Button $submitButton;
    protected Container $submitLine;

    public function __construct(InputForm $form)
    {
        parent::__construct();
        $this->setComponentClass("FormRenderer");
        $this->setLayout($this->layout);

        $this->setForm($form);

        $this->setEnctype(Form::ENCTYPE_MULTIPART);
        $this->setMethod(Form::METHOD_POST);
        $this->setLayout(FormRenderer::LAYOUT_VBOX);

        $this->submitButton = Button::SubmitButton(FormRenderer::SUBMIT_NAME);

        $this->submitLine = new Container(false);
        $this->submitLine->setComponentClass("SubmitLine");

        $textSpace = new Container(false);
        $textSpace->setComponentClass("TextSpace");
        $this->submitLine->items()->append($textSpace);

        $buttons = new Container(false);
        $buttons->setComponentClass("Buttons");

        $buttons->items()->append($this->submitButton);
        $this->submitLine->items()->append($buttons);


        $this->items()->append(new ClosureComponent($this->renderInputs(...), false));
        $this->items()->append(new ClosureComponent($this->renderSubmitLine(...), false));

    }

    public function getSubmitLine(): Container
    {
        return $this->submitLine;
    }

    public function getButtons(): Container
    {
        $buttons = $this->submitLine->items()->getByComponentClass("Buttons");
        if ($buttons instanceof Container) return $buttons;

        throw new Exception("Buttons container not found");
    }

    public function getSubmitButton(): Button
    {
        return $this->submitButton;
    }

    public function getTextSpace(): Container
    {
        $textSpace = $this->submitLine->items()->getByComponentClass("TextSpace");
        if ($textSpace instanceof Container) return $textSpace;

        throw new Exception("TextSpace container not found");
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/FormRenderer.css";

        return $arr;
    }

    public function setLayout(string $mode) : void
    {
        $this->layout = $mode;
    }


    /**
     * Assign form and copying and copy the form name to $this->name
     * @param InputForm $form
     * @return void
     */
    public function setForm(InputForm $form) : void
    {
        $this->form = $form;
        $this->form->setRenderer($this);
        $this->setName($form->getName());
    }

    public function getForm() : InputForm
    {
        return $this->form;
    }

    /**
     * Assign 'method' and 'layout' properties as attibutes
     * @return void
     */
    protected function processAttributes(): void
    {

        parent::processAttributes();
        $this->setAttribute("layout", $this->layout);

    }

    protected function renderInputs() : void
    {
        $group_names = $this->form->getGroupNames();

        foreach ($group_names as $group_name) {

            $group = $this->form->getGroup($group_name);

            $inputNames = $group->inputNames();
            if (count($inputNames)<1) continue;

            $container = null;
            if (count($group_names)==1 && strcmp($group_name, InputForm::DEFAULT_GROUP)==0) {
                //single group - with name default no rendering using input group
                $container = new Container(false);
                $container->wrapper_enabled = false;
            }
            else {
                $container = new InputGroupRenderer($group);
            }

            foreach ($inputNames as $idx2=>$inputName) {
                $input = $this->form->getInput($inputName);
                $container->items()->append($this->createInputComponent($input));
            }

            $container->render();


        }

    }

    protected function createInputComponent(DataInput $input) : Component
    {
        return new InputComponent($input);
    }

    protected function renderSubmitLine() : void
    {
        //default is to use the submit name of the form
        $submit_value = $this->form->getName();

        if ($this->submitLine->isRenderEnabled()) {

            //rare but honor the button value if set
            if (!$this->submitButton->getValue()) {
                $this->submitButton->setValue($submit_value);
            }
            $this->submitLine->render();

        }
        else {
            //submit line is hidden render hidden input to signal the processor
            $submit = new Input("hidden");
            $submit->setName(FormRenderer::SUBMIT_NAME);
            $submit->setValue($submit_value);
            $submit->render();
        }
    }

}

?>
