<?php

include_once("lib/input/renderers/TextField.php");
include_once("lib/input/renderers/DateField.php");
include_once("lib/input/renderers/PasswordField.php");
include_once("lib/input/renderers/SelectField.php");
include_once("lib/input/renderers/TextArea.php");
include_once("lib/input/renderers/CheckField.php");
include_once("lib/input/renderers/RadioField.php");
include_once("lib/input/renderers/HiddenField.php");
include_once("lib/input/renderers/TimeField.php");
include_once("lib/input/renderers/PhoneField.php");
include_once("lib/input/renderers/MCETextArea.php");
include_once("lib/input/renderers/NestedSelectField.php");
include_once("lib/input/renderers/ImageField.php");
include_once("lib/input/renderers/FileField.php");
include_once("lib/input/renderers/SliderField.php");
include_once("lib/input/renderers/SessionImageField.php");
include_once("lib/input/renderers/SessionFileField.php");
include_once("lib/input/renderers/ColorCodeField.php");


include_once("lib/input/validators/EmailValidator.php");
include_once("lib/input/validators/DateValidator.php");
include_once("lib/input/validators/TimeValidator.php");
include_once("lib/input/validators/PhoneValidator.php");
include_once("lib/input/validators/PasswordValidator.php");
include_once("lib/input/validators/ArrayInputValidator.php");
include_once("lib/input/validators/ImageUploadValidator.php");
include_once("lib/input/validators/FileUploadValidator.php");
include_once("lib/input/validators/EmptyValueValidator.php");


include_once("lib/input/processors/DateInputProcessor.php");
include_once("lib/input/processors/TimeInputProcessor.php");
include_once("lib/input/processors/PhoneInputProcessor.php");
include_once("lib/input/processors/UploadDataInputProcessor.php");

include_once("lib/input/processors/SessionUploadInputProcessor.php");

include_once("lib/input/ArrayInputField.php");

include_once("lib/selectors/ArraySelector.php");


class InputFactory
{

  const TEXTFIELD=1;
  const TEXTFIELD_PASSWORD=2;
  const TEXTAREA=3;
  const SELECT=4;
  const SELECT_MULTI=5;
  const RADIO=6;
  const CHECKBOX=7;
  const CHECKBOX_ARRAY=8;
  const PHONE=9;
  const DATE=10;
  const TIME=11;
  const HIDDEN=12;
  const FILE=13;
  const IMAGE=14;
  
  const EMAIL=15;
  const NESTED_SELECT=16;
  const MCE_TEXTAREA=17;
  const DYNAMIC_PAGE=18;
  const SESSION_IMAGE=19;
  const SESSION_FILE=20;
  const COLORCODE=21;
  
  const HIDDEN_ARRAY=100;
  const TEXTFIELD_ARRAY=101;
  const SELECT_ARRAY=102;

  const SLIDER = 200;

  public static function CreateField($type, $name, $label, $required)
  {
	  $field = new InputField($name, $label, $required);
	  $field->transact_mode = InputField::TRANSACT_VALUE;
	  
	  $field->setValidator(new EmptyValueValidator());
	  $processor = new BeanPostProcessor();
	  $field->setProcessor($processor);

	  switch($type) {

		case InputFactory::TEXTFIELD:
			$field->setRenderer(new TextField());

			break;
		case InputFactory::COLORCODE:
			$field->setRenderer(new ColorCodeField());

			break;
		case InputFactory::EMAIL:
			$field->setRenderer(new TextField());
			$field->setValidator(new EmailValidator());
			break;
		case InputFactory::TEXTAREA:
			$field->setRenderer(new TextArea());
			break;

		case InputFactory::SELECT:
			$field->setRenderer(new SelectField());
			$field->getProcessor()->transact_empty_string_as_null = true;
			break;
			
		case InputFactory::SELECT_MULTI:
			$field->setRenderer(new SelectMultipleField());
			break;

		case InputFactory::TEXTFIELD_PASSWORD:
			$field->setRenderer(new PasswordField());
			$field->setValidator(new PasswordValidator());

			break;
		case InputFactory::HIDDEN:
			$field->setRenderer(new HiddenField());

			break;
		case InputFactory::CHECKBOX:
			$field->setRenderer(new CheckField());
			break;

		case InputFactory::RADIO:
			$field->setRenderer(new RadioField());
			break;

		case InputFactory::MCE_TEXTAREA:
			$field->setRenderer(new MCETextArea());
			break;

		case InputFactory::DATE:
			$field->setRenderer(new DateField());
			$field->setProcessor(new DateInputProcessor());
			$field->setValidator(new DateValidator());
			break;

		case InputFactory::TIME:
			$field->setRenderer(new TimeField());
			$field->setProcessor(new TimeInputProcessor());
			$field->setValidator(new TimeValidator());
			break;

		case InputFactory::PHONE:
			$field->setRenderer(new PhoneField());
			$field->setProcessor(new PhoneInputProcessor());
			$field->setValidator(new PhoneValidator());

			break;

		case InputFactory::FILE:
			$field->setRenderer(new FileField());
			$field->setValidator(new FileUploadValidator());
			$field->setProcessor(new UploadDataInputProcessor());
			break;

		case InputFactory::IMAGE:
			$field->setRenderer(new ImageField());
			$field->setValidator(new ImageUploadValidator());
			$field->setProcessor(new UploadDataInputProcessor());
			break;


		case InputFactory::NESTED_SELECT:
			$field->setRenderer(new NestedSelectField());
			break;

		case InputFactory::SLIDER:
			$field->setRenderer(new SliderField());
			break;
			
		case InputFactory::SESSION_IMAGE:
		
			$field = new ArrayInputField($name, $label, $required);
			$field->transact_mode = InputField::TRANSACT_OBJECT;
			$field->allow_dynamic_addition = false;
			
			$field->setRenderer(new SessionImageField());
			
			$processor = new SessionUploadInputProcessor();
			$processor->max_slots = 1;
			$field->setProcessor($processor);
			
			$validator = new ImageUploadValidator();
			$validator->skip_is_uploaded_check = true;
			$field->setValidator($validator);

			$field->setValueTransactor($processor);
			
			break;
		case InputFactory::SESSION_FILE:
		
			$field = new ArrayInputField($name, $label, $required);
			$field->transact_mode = InputField::TRANSACT_OBJECT;
			$field->allow_dynamic_addition = false;
			
			$field->setRenderer(new SessionFileField());
			
			$processor = new SessionUploadInputProcessor();
			$processor->max_slots = 1;
			$field->setProcessor($processor);
			
			$validator = new FileUploadValidator();
			$validator->skip_is_uploaded_check = true;
			$field->setValidator($validator);

			$field->setValueTransactor($processor);
			
			break;
		default:
		  throw new Exception("Unknown field type: ".$type);
		  break;


	  }
	  return $field;

  }

}

?>