<?php
include_once("objects/data/DataObject.php");

include_once("input/renderers/InputLabel.php");
include_once("input/renderers/InputField.php");
include_once("input/validators/EmptyValueValidator.php");
include_once("input/processors/InputProcessor.php");


/**
 * Generic class representing a data value (input in form linked to table row field value)
 */
class DataInput extends DataObject
{

    public bool $skip_search_filter_processing = false;


    /**
     * @var string
     */
    protected string $label = "";
    /**
     * @var bool
     */
    protected bool $required = false;

    /**
     * @var bool
     */
    protected bool $editable = true;

    /**
     * @var string|array
     */
    protected string|array $error = "";

    protected ?IInputValidator $validator = null;

    protected ?InputProcessor $processor = null;

    protected ?InputForm $form = null;

    protected ?InputField $renderer = null;

    protected mixed $user_data = null;


    /**
     * Signal InputComponent to enable the BeanTranslationDialog
     * @var bool
     */
    protected bool $translator_enabled = false;

    public function __construct(string $name, string $label, bool $required)
    {
        parent::__construct();

        $this->name = $name;
        $this->label = $label;
        $this->required = $required;

        $this->validator = new EmptyValueValidator();
        $this->processor = new InputProcessor($this);

        $this->form = NULL;

        $this->editable = TRUE;
        $this->user_data = NULL;
        $this->translator_enabled = FALSE;
    }

    /**
     * Clear the value and error of this input
     * @return void
     */
    public function clear() : void
    {
        $this->value = "";
        $this->error = "";
    }

    /**
     * Validate and set error
     * Call IInputValidator validate method
     * Error field is set with exception thrown
     * If field is not 'required' value is set to null
     * @return void
     */
    public function validate() : void
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


    public function setLabel(string $str): void
    {
        $this->label = $str;
    }
    public function getLabel(): string
    {
        return $this->label;
    }

    public function setRequired(bool $mode): void
    {
        $this->required = $mode;
    }
    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setProcessor(InputProcessor $processor) : void
    {
        $this->processor = $processor;
        $this->processor->setDataInput($this);
    }
    public function getProcessor(): InputProcessor
    {
        return $this->processor;
    }

    public function setValidator(IInputValidator $validator): void
    {
        $this->validator = $validator;
    }

    public function getValidator(): IInputValidator
    {
        return $this->validator;
    }

    public function setRenderer(InputField $renderer): void
    {
        $this->renderer = $renderer;
    }
    public function getRenderer(): InputField
    {
        return $this->renderer;
    }

    public function setForm(InputForm $form): void
    {
        $this->form = $form;
    }
    public function getForm(): ?InputForm
    {
        return $this->form;
    }

    /**
     * Set the error text for this input
     * @param string $err
     * @return void
     */
    public function setError(string $err) : void
    {
        $this->error = $err;
    }
    /**
     * Get the error text for this input
     * @return string
     */
    public function getError() : string
    {
        return $this->error;
    }

    public function haveError(): bool
    {
        if ($this->error && strlen($this->error)>0) return true;
        return false;
    }

    public function setEditable(bool $editable): void
    {
        $this->editable = $editable;
    }
    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function setUserData($data): void
    {
        $this->user_data = $data;
    }
    public function getUserData()
    {
        return $this->user_data;
    }

    public function enableTranslator(bool $mode) : void
    {
        $this->translator_enabled = $mode;
    }
    public function translatorEnabled(): bool
    {
        return $this->translator_enabled;
    }

}

?>
