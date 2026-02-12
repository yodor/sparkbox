<?php
include_once("input/ArrayDataInput.php");
include_once("input/DataInput.php");

include_once("input/renderers/TextField.php");
include_once("input/renderers/DateField.php");
include_once("input/renderers/PasswordField.php");
include_once("input/renderers/SelectField.php");
include_once("input/renderers/TextArea.php");
include_once("input/renderers/CheckField.php");
include_once("input/renderers/RadioField.php");
include_once("input/renderers/HiddenField.php");
include_once("input/renderers/TimeField.php");
include_once("input/renderers/PhoneField.php");
include_once("input/renderers/MCETextArea.php");
include_once("input/renderers/NestedSelectField.php");
include_once("input/renderers/SliderField.php");
include_once("input/renderers/SessionImage.php");
include_once("input/renderers/SessionFile.php");
include_once("input/renderers/ColorCodeField.php");
include_once("input/renderers/TextCaptchaField.php");
include_once("input/renderers/CheckboxTreeView.php");

include_once("input/validators/EmailValidator.php");
include_once("input/validators/DateValidator.php");
include_once("input/validators/TimeValidator.php");
include_once("input/validators/PhoneValidator.php");
include_once("input/validators/PasswordValidator.php");
include_once("input/validators/ArrayInputValidator.php");
include_once("input/validators/ImageUploadValidator.php");
include_once("input/validators/FileUploadValidator.php");
include_once("input/validators/EmptyValueValidator.php");
include_once("input/validators/TextCaptchaValidator.php");

include_once("input/processors/UploadDataInput.php");

include_once("input/processors/SessionUploadInput.php");

enum InputType : int {
    case TEXT = 1;
    case PASSWORD = 2;
    case TEXTAREA = 3;
    case SELECT = 4;
    case SELECT_MULTI = 5;
    case RADIO = 6;
    case CHECKBOX = 7;
    case CHECKBOX_ARRAY = 8;
    case PHONE = 9;
    case DATE = 10;
    case TIME = 11;
    case HIDDEN = 12;

    case EMAIL = 15;
    case NESTED_SELECT = 16;
    case MCE_TEXTAREA = 17;
    case DYNAMIC_PAGE = 18;
    case SESSION_IMAGE = 19;
    case SESSION_FILE = 20;
    case COLOR_CODE = 21;

    case CAPTCHA_TEXT = 23;

    case HIDDEN_ARRAY = 100;
    case TEXT_ARRAY = 101;
    case SELECT_ARRAY = 102;

    case SLIDER = 200;

    case CHECKBOX_TREEVIEW = 201;
}
class DataInputFactory
{


    /**
     * Create new DataInput of the given '$type'
     *
     * @param InputType $type DataInput type to create
     * @param string $name Name to use for this InputField
     * @param string $label Label to use for this InputField
     * @param bool $required Flag to set if this field requires value
     * @return DataInput
     * @throws Exception
     */
    public static function Create(InputType $type, string $name, string $label, bool $required) : DataInput
    {
        $input = new DataInput($name, $label, $required);

        $input->setValidator(new EmptyValueValidator());
        $processor = new InputProcessor($input);

        switch ($type) {

            case InputType::TEXT:
                new TextField($input);
                break;

            case InputType::COLOR_CODE:
                new ColorCodeField($input);
                break;

            case InputType::EMAIL:
                new TextField($input);
                $input->setValidator(new EmailValidator());
                break;

            case InputType::TEXTAREA:
                new TextArea($input);
                break;

            case InputType::SELECT:
                new SelectField($input);
                $input->getProcessor()->transact_empty_string_as_null = TRUE;
                break;

            case InputType::SELECT_MULTI:
                new SelectMultipleField($input);
                break;

            case InputType::PASSWORD:
                new PasswordField($input);
                $input->setValidator(new PasswordValidator());
                break;

            case InputType::HIDDEN:
                new HiddenField($input);
                break;

            case InputType::CHECKBOX:
                new CheckField($input);
                break;

            case InputType::RADIO:
                new RadioField($input);
                break;

            case InputType::MCE_TEXTAREA:
                new MCETextArea($input);
                break;

            case InputType::DATE:
                new DateField($input);
                $input->setValidator(new DateValidator());
                break;

            case InputType::TIME:
                new TimeField($input);
                $input->setValidator(new TimeValidator());
                break;

            case InputType::PHONE:
                new PhoneField($input);
                $input->setValidator(new PhoneValidator());
                break;


            case InputType::NESTED_SELECT:
                new NestedSelectField($input);
                break;

            case InputType::SLIDER:
                new SliderField($input);
                break;

            case InputType::SESSION_IMAGE:

                $input = new ArrayDataInput($name, $label, $required);

                new SessionImage($input);

                $processor = new SessionUploadInput($input);

                $validator = new ImageUploadValidator();
                $input->setValidator($validator);

                break;
            case InputType::SESSION_FILE:

                $input = new ArrayDataInput($name, $label, $required);

                new SessionFile($input);

                $processor = new SessionUploadInput($input);

                $validator = new FileUploadValidator();
                $input->setValidator($validator);

                break;

            case InputType::CAPTCHA_TEXT:

                $input = new DataInput($name, $label, $required);
                new TextCaptchaField($input);
                $input->setValidator(new TextCaptchaValidator());
                break;

            case InputType::CHECKBOX_TREEVIEW:

                $input = new ArrayDataInput($name, $label, $required);
                new CheckboxTreeView($input);
                break;

            default:
                throw new Exception("Unknown input type: " . $type->name);

        }
        return $input;

    }

}