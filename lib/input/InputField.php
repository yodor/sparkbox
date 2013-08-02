<?php
include_once("lib/beans/IDataBeanSource.php");
include_once("lib/input/validators/EmptyValueValidator.php");
include_once("lib/input/renderers/LabelRenderer.php");
include_once("lib/input/processors/BeanPostProcessor.php");

//setting a databeansource to the field makes it work with values fetched from the data source
class InputField implements IDataBeanSource
{

  //transact DBROW without source is incompatible with non required field.
  const TRANSACT_DBROW = 1;
  const TRANSACT_OBJECT = 2;
  const TRANSACT_VALUE = 3;
  
  public $transact_mode = InputField::TRANSACT_VALUE;

  public $content_after = "";
  public $content_before = "";
  public $skip_transaction = false;

  public $accepted_tags="";

  protected $label;
  protected $name;
  protected $required;
  protected $script_required;
  protected $input_type;

  protected $value;
  protected $error;

  protected $form;

  protected $renderer;

  protected $translator_enabled;

  protected $label_renderer;

  protected $validator;

  protected $editable;

  protected $user_data;

  protected $input_processor;

  protected $value_transactor = null;

  
  
  //when set to true will skip this field in search filter construction in searchFilter()
  public $skip_search_filter_processing = false;


  protected $link_field = NULL;
  protected $link_mode = false;

  protected $data_source = NULL;
  
  public function setSource(DBTableBean $data_source)
  {
      debug("InputField: ['".$this->getName()."'] Setting source: ".get_class($data_source));
      
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

      return null;

  }
  public function __construct($name, $label, $required)
  {
	  $this->label = $label;
	  $this->name = $name;
	  $this->required = ($required>0) ? true : false;

	  $this->value = "";
	  $this->error = "";

	  $this->form=false;
	  $this->user_data=false;
	  $this->translator_enabled=false;
	  $this->editable = true;


	  $this->label_renderer = new LabelRenderer();

	  $this->validator = new EmptyValueValidator();

	  $this->input_processor = new BeanPostProcessor();

	  $this->accepted_tags = DefaultAcceptedTags();

	  $this->data_source = NULL;
  }
  
	public function setLinkField(InputField $field)
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
  public function getRenderer() {
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
  public function setEditable($editable)
  {
	  $this->editable=$editable;
  }

  public function setForm($form){
	  $this->form=$form;
  }
  public function getForm() {
	  return $this->form;
  }
  public function getLabel() {
	  return $this->label;
  }
  public function setLabel($str)
  {
	  $this->label = $str;
  }
  public function getName() {
	  return $this->name;
  }
  public function setName($name)
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
  public function setRequired($mode)
  {
	  $this->required = ($mode ? 1 : 0);
  }
  public function setScriptRequired($mode)
  {
	  $this->script_required = ($mode ? 1 : 0);
  }

  public function getValue() {
	  return $this->value;
  }
  public function setValue($value) {
	  $this->value = $value;
  }
  public function getError() {
	  return $this->error;
  }

  public function setError($err) {
	  $this->error=$err;
  }
  public function haveError() {
	  return (strlen($this->error)>0);
  }
  public function  clear()
  {
	  $this->value="";
	  $this->error="";
  }
  public function getErrorText($render_index)
  {
	  $error_text = "";

	  if ($render_index>-1) {

		  $error_text = $this->getErrorAt($render_index);

	  }
	  else {

		  $error_text = $this->getError();
	  }

	  return $error_text;
  }

  //comming from user posts. can throw exception
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

  public function enableTranslator($mode)
  {
      $this->translator_enabled=$mode;
  }
  public function translatorEnabled()
  {
      return $this->translator_enabled;
  }
}
?>