<?php
include_once("lib/beans/IDataBeanSource.php");
include_once("lib/input/validators/EmptyValueValidator.php");
include_once("lib/input/renderers/InputLabel.php");
include_once("lib/input/processors/BeanPostProcessor.php");

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

class DataInput implements IDataBeanSource
{

    //transact DBROW without source is incompatible with non required field.
    const TRANSACT_DBROW = 1;
    const TRANSACT_OBJECT = 2;
    const TRANSACT_VALUE = 3;

    //int
    public $transact_mode = DataInput::TRANSACT_VALUE;

    public $content_after = "";
    public $content_before = "";

    public $accepted_tags = "";

    public $skip_transaction = false;

    //when set to true will skip this field in search filter construction in searchFilter()
    public $skip_search_filter_processing = false;


    protected $name;
    protected $label;
    protected $required;

    protected $value;

    protected $error;


    protected $script_required;
    protected $input_type;

    //InputForm is owning this InputField
    protected $form = NULL;

    //IFieldRenderer is rendering the $value of this InputField
    protected $renderer = NULL;

    //LabelRenderer is rendering the $label of this InputField
    protected $label_renderer = NULL;

    //IInputValidator is responsible for validation of the $value data
    protected $validator = NULL;
    //IBeanPostProcessor
    protected $input_processor = NULL;
    //IDBFieldTransactor
    protected $value_transactor = NULL;
    //DBTableBean
    protected $data_source = NULL;

    protected $editable;

    protected $user_data;

    //bool
    protected $translator_enabled = false;

    protected $link_field = NULL;
    protected $link_mode = false;


    public function setSource(DBTableBean $data_source)
    {
        debug("InputField: ['" . $this->getName() . "'] Setting source: " . get_class($data_source));

        $this->data_source = $data_source;
    }

    public function getSource()
    {
        return $this->data_source;
    }

    public function setValueTransactor(IDBFieldTransactor $transactor)
    {
        $this->value_transactor = $transactor;
    }

    public function getValueTransactor()
    {

        if ($this->value_transactor instanceof IDBFieldTransactor) return $this->value_transactor;
        if ($this->input_processor instanceof IDBFieldTransactor) return $this->input_processor;

        return NULL;

    }

    public function __construct(string $name, string $label, bool $required)
    {
        $this->label = $label;
        $this->name = $name;
        $this->required = ($required > 0) ? true : false;

        $this->value = "";
        $this->error = "";

        $this->form = NULL;
        $this->user_data = false;
        $this->translator_enabled = false;
        $this->editable = true;


        $this->label_renderer = new InputLabel();

        $this->validator = new EmptyValueValidator();

        $this->input_processor = new BeanPostProcessor();

        $this->accepted_tags = DefaultAcceptedTags();

        $this->data_source = NULL;
    }

    public function setLinkField(DataInput $field)
    {
        $this->link_field = $field;
    }

    public function getLinkField()
    {
        return $this->link_field;
    }

    public function setLinkMode($mode)
    {
        $this->link_mode = $mode;
    }

    public function getLinkMode()
    {
        return $this->link_mode;
    }

    public function setProcessor(IBeanPostProcessor $ip)
    {
        $this->input_processor = $ip;
    }

    public function getProcessor()
    {
        return $this->input_processor;
    }

    public function setValidator(IInputValidator $validator)
    {
        $this->validator = $validator;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function setRenderer(IFieldRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function getRenderer()
    {
        return $this->renderer;
    }

    public function getRenderPrivate()
    {
        return $this->renderer;
    }

    public function setLabelRenderer(ILabelRenderer $label_renderer)
    {
        $this->label_renderer = $label_renderer;
    }

    public function getLabelRenderer()
    {
        return $this->label_renderer;
    }


    public function setUserData($data)
    {
        $this->user_data = $data;
    }

    public function getUserData()
    {
        return $this->user_data;
    }

    public function isEditable()
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

    public function getForm()
    {
        return $this->form;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($str)
    {
        $this->label = $str;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function isRequired()
    {
        return ((int)$this->required > 0) ? true : false;
    }

    public function isScriptRequired()
    {
        return ((int)$this->script_required > 0) ? true : false;
    }

    public function setRequired(bool $mode)
    {
        $this->required = ($mode ? 1 : 0);
    }

    public function setScriptRequired(bool $mode)
    {
        $this->script_required = ($mode ? 1 : 0);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError(string $err)
    {
        $this->error = $err;
    }

    public function haveError()
    {
        return (strlen($this->error) > 0);
    }

    public function clear()
    {
        $this->value = "";
        $this->error = "";
    }

    public function getErrorText(int $render_index)
    {
        $error_text = "";

        if ($render_index > -1) {

            $error_text = $this->getErrorAt($render_index);

        }
        else {

            $error_text = $this->getError();
        }

        return $error_text;
    }

    //coming from user posts. can throw exception
    public function loadPostData(array $arr)
    {
        $this->input_processor->loadPostData($this, $arr);
    }

    //validate sets error on the field
    public function validate()
    {
        try {

            $this->validator->validateInput($this);
        }
        catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function enableTranslator(bool $mode)
    {
        $this->translator_enabled = $mode;
    }

    public function translatorEnabled()
    {
        return $this->translator_enabled;
    }
}

?>