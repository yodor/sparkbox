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
include_once("lib/input/renderers/SessionImage.php");
include_once("lib/input/renderers/SessionFile.php");
include_once("lib/input/renderers/ColorCodeField.php");
include_once("lib/input/renderers/CaptchaInputField.php");

include_once("lib/input/validators/EmailValidator.php");
include_once("lib/input/validators/DateValidator.php");
include_once("lib/input/validators/TimeValidator.php");
include_once("lib/input/validators/PhoneValidator.php");
include_once("lib/input/validators/PasswordValidator.php");
include_once("lib/input/validators/ArrayInputValidator.php");
include_once("lib/input/validators/ImageUploadValidator.php");
include_once("lib/input/validators/FileUploadValidator.php");
include_once("lib/input/validators/EmptyValueValidator.php");
include_once("lib/input/validators/CaptchaInputValidator.php");

include_once("lib/input/processors/DateInputProcessor.php");
include_once("lib/input/processors/TimeInputProcessor.php");
include_once("lib/input/processors/PhoneInputProcessor.php");
include_once("lib/input/processors/UploadDataInputProcessor.php");

include_once("lib/input/processors/SessionUploadInputProcessor.php");

include_once("lib/input/ArrayDataInput.php");

include_once("lib/selectors/ArraySelector.php");


class DataInputFactory
{

    const TEXTFIELD = 1;
    const TEXTFIELD_PASSWORD = 2;
    const TEXTAREA = 3;
    const SELECT = 4;
    const SELECT_MULTI = 5;
    const RADIO = 6;
    const CHECKBOX = 7;
    const CHECKBOX_ARRAY = 8;
    const PHONE = 9;
    const DATE = 10;
    const TIME = 11;
    const HIDDEN = 12;
    const FILE = 13;
    const IMAGE = 14;

    const EMAIL = 15;
    const NESTED_SELECT = 16;
    const MCE_TEXTAREA = 17;
    const DYNAMIC_PAGE = 18;
    const SESSION_IMAGE = 19;
    const SESSION_FILE = 20;
    const COLORCODE = 21;

    const CAPTCHA = 22;

    const HIDDEN_ARRAY = 100;
    const TEXTFIELD_ARRAY = 101;
    const SELECT_ARRAY = 102;

    const SLIDER = 200;

    /**
     * @param int $type
     * @param string $name
     * @param string $label
     * @param bool $required
     * @return ArrayDataInput|DataInput
     * @throws Exception
     */
    public static function Create(int $type, string $name, string $label, bool $required)
    {
        $field = new DataInput($name, $label, $required);
        $field->transact_mode = DataInput::TRANSACT_VALUE;

        $field->setValidator(new EmptyValueValidator());
        $processor = new BeanPostProcessor();
        $field->setProcessor($processor);

        switch ($type) {

            case DataInputFactory::TEXTFIELD:
                $field->setRenderer(new TextField());

                break;
            case DataInputFactory::COLORCODE:
                $field->setRenderer(new ColorCodeField());

                break;
            case DataInputFactory::EMAIL:
                $field->setRenderer(new TextField());
                $field->setValidator(new EmailValidator());
                break;
            case DataInputFactory::TEXTAREA:
                $field->setRenderer(new TextArea());
                break;

            case DataInputFactory::SELECT:
                $field->setRenderer(new SelectField());
                $field->getProcessor()->transact_empty_string_as_null = true;
                break;

            case DataInputFactory::SELECT_MULTI:
                $field->setRenderer(new SelectMultipleField());
                break;

            case DataInputFactory::TEXTFIELD_PASSWORD:
                $field->setRenderer(new PasswordField());
                $field->setValidator(new PasswordValidator());

                break;
            case DataInputFactory::HIDDEN:
                $field->setRenderer(new HiddenField());

                break;
            case DataInputFactory::CHECKBOX:
                $field->setRenderer(new CheckField());
                break;

            case DataInputFactory::RADIO:
                $field->setRenderer(new RadioField());
                break;

            case DataInputFactory::MCE_TEXTAREA:
                $field->setRenderer(new MCETextArea());
                break;

            case DataInputFactory::DATE:
                $field->setRenderer(new DateField());
                $field->setProcessor(new DateInputProcessor());
                $field->setValidator(new DateValidator());
                break;

            case DataInputFactory::TIME:
                $field->setRenderer(new TimeField());
                $field->setProcessor(new TimeInputProcessor());
                $field->setValidator(new TimeValidator());
                break;

            case DataInputFactory::PHONE:
                $field->setRenderer(new PhoneField());
                $field->setProcessor(new PhoneInputProcessor());
                $field->setValidator(new PhoneValidator());

                break;

            case DataInputFactory::FILE:
                $field->setRenderer(new FileField());
                $field->setValidator(new FileUploadValidator());
                $field->setProcessor(new UploadDataInputProcessor());
                break;

            case DataInputFactory::IMAGE:
                $field->setRenderer(new ImageField());
                $field->setValidator(new ImageUploadValidator());
                $field->setProcessor(new UploadDataInputProcessor());
                break;


            case DataInputFactory::NESTED_SELECT:
                $field->setRenderer(new NestedSelectField());
                break;

            case DataInputFactory::SLIDER:
                $field->setRenderer(new SliderField());
                break;

            case DataInputFactory::SESSION_IMAGE:

                $field = new ArrayDataInput($name, $label, $required);
                $field->transact_mode = DataInput::TRANSACT_OBJECT;
                $field->allow_dynamic_addition = false;

                $field->setArrayRenderer(new SessionImage());

                $processor = new SessionUploadInputProcessor();
                $processor->max_slots = 1;
                $field->setProcessor($processor);

                $validator = new ImageUploadValidator();
                $validator->skip_is_uploaded_check = true;
                $field->setValidator($validator);

                $field->setValueTransactor($processor);

                break;
            case DataInputFactory::SESSION_FILE:

                $field = new ArrayDataInput($name, $label, $required);
                $field->transact_mode = DataInput::TRANSACT_OBJECT;
                $field->allow_dynamic_addition = false;

                $field->setArrayRenderer(new SessionFile());

                $processor = new SessionUploadInputProcessor();
                $processor->max_slots = 1;
                $field->setProcessor($processor);

                $validator = new FileUploadValidator();
                $validator->skip_is_uploaded_check = true;
                $field->setValidator($validator);

                $field->setValueTransactor($processor);

                break;
            case DataInputFactory::CAPTCHA:

                $field = new DataInput($name, $label, $required);
                $field->setRenderer(new CaptchaInputField());
                $field->setValidator(new CaptchaInputValidator());
                break;

            default:
                throw new Exception("Unknown field type: " . $type);
                break;


        }
        return $field;

    }

}

?>
