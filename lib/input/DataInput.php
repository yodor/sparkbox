<?php
include_once("input/validators/EmptyValueValidator.php");
include_once("input/renderers/InputLabel.php");
include_once("input/renderers/InputField.php");
include_once("input/processors/InputProcessor.php");

//
//Generic class representing a data value (input in form linked to table row field value)
//Properties:
//- Name: The name of this data element;
//- Value: The value of this data element;
//- Label: The label of this data element;
//- Required: The value is required to be non-empty
//Notes:
//- can be linked with other fields (parent/child)
//- data value is validated using the assigned IInputValidator
//- label is rendered using the assigned ILabelRenderer - default is LabelRenderer component
//- value is rendered using the assigned IFieldRenderer - no default assigned
//- transacts the data value to a DB row field value using IDBFieldTransactor.
//- setting a IDataBeanSource to the field makes it work with values fetched from the data source

class DataInput
{

    public $skip_search_filter_processing = FALSE;

    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $label;
    /**
     * @var bool
     */
    protected $required;

    protected $value;

    protected $error;

    /**
     * DataInput is part of form
     * @var InputForm
     */
    protected $form = NULL;

    /**
     * Value rendering is done using InputField
     * @var InputField|null
     */
    protected $renderer = NULL;


    //IInputValidator is responsible for validation of the $value data
    protected $validator = NULL;
    //InputProcessor
    protected $processor = NULL;

    /**
     * @var DBTableBean|null
     */
    protected $bean;

    /**
     * @var bool
     */
    protected $editable;

    /**
     * @var null
     */
    protected $user_data;

    /**
     * @var bool
     */
    protected $translator_enabled = FALSE;


    public function __construct(string $name, string $label, bool $required)
    {
        $this->label = $label;
        $this->name = $name;
        $this->required = $required;

        $this->form = NULL;
        $this->user_data = NULL;
        $this->translator_enabled = FALSE;
        $this->editable = TRUE;


        $this->validator = new EmptyValueValidator();
        $this->processor = new InputProcessor($this);



        $this->bean = NULL;
    }

    public function setProcessor(InputProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getProcessor(): InputProcessor
    {
        return $this->processor;
    }

    public function setValidator(IInputValidator $validator)
    {
        $this->validator = $validator;
    }

    public function getValidator(): IInputValidator
    {
        return $this->validator;
    }

    public function setRenderer(InputField $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return InputField
     */
    public function getRenderer(): InputField
    {
        return $this->renderer;
    }

    public function setUserData($data)
    {
        $this->user_data = $data;
    }

    public function getUserData()
    {
        return $this->user_data;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function setEditable(bool $editable)
    {
        $this->editable = $editable;
    }

    public function setForm(InputForm $form)
    {
        $this->form = $form;
    }

    public function getForm(): ?InputForm
    {
        return $this->form;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $str)
    {
        $this->label = $str;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $mode)
    {
        $this->required = $mode;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string|array
     */
    public function getError()
    {
        return $this->error;
    }

    public function setError(string $err)
    {
        $this->error = $err;
    }

    public function haveError(): bool
    {
        return (strlen($this->error) > 0);
    }

    public function clear()
    {
        $this->value = "";
        $this->error = "";
    }

    //validate sets error on the field
    public function validate()
    {
        try {
            $this->validator->validate($this);
        }
        catch (Exception $e) {
            if ($this->isRequired()) {
                $this->setError($e->getMessage());
            }
            else {
                $this->setValue(NULL);
            }
        }
    }

    public function enableTranslator(bool $mode)
    {
        $this->translator_enabled = $mode;
    }

    public function translatorEnabled(): bool
    {
        return $this->translator_enabled;
    }
}

?>