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

include_once("input/processors/DateInput.php");
include_once("input/processors/TimeInput.php");
include_once("input/processors/PhoneInput.php");
include_once("input/processors/UploadDataInput.php");

include_once("input/processors/SessionUploadInput.php");

class DataInputFactory
{

    const TEXT = 1;
    const PASSWORD = 2;
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

    const EMAIL = 15;
    const NESTED_SELECT = 16;
    const MCE_TEXTAREA = 17;
    const DYNAMIC_PAGE = 18;
    const SESSION_IMAGE = 19;
    const SESSION_FILE = 20;
    const COLOR_CODE = 21;

    const CAPTCHA_TEXT = 23;

    const HIDDEN_ARRAY = 100;
    const TEXT_ARRAY = 101;
    const SELECT_ARRAY = 102;

    const SLIDER = 200;

    const CHECKBOX_TREEVIEW = 201;

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
        $input = new DataInput($name, $label, $required);

        $input->setValidator(new EmptyValueValidator());
        $processor = new InputProcessor($input);

        switch ($type) {

            case DataInputFactory::TEXT:
                new TextField($input);
                break;

            case DataInputFactory::COLOR_CODE:
                new ColorCodeField($input);
                break;

            case DataInputFactory::EMAIL:
                new TextField($input);
                $input->setValidator(new EmailValidator());
                break;

            case DataInputFactory::TEXTAREA:
                new TextArea($input);
                break;

            case DataInputFactory::SELECT:
                new SelectField($input);
                $input->getProcessor()->transact_empty_string_as_null = TRUE;
                break;

            case DataInputFactory::SELECT_MULTI:
                new SelectMultipleField($input);
                break;

            case DataInputFactory::PASSWORD:
                new PasswordField($input);
                $input->setValidator(new PasswordValidator());
                break;

            case DataInputFactory::HIDDEN:
                new HiddenField($input);
                break;

            case DataInputFactory::CHECKBOX:
                new CheckField($input);
                break;

            case DataInputFactory::RADIO:
                new RadioField($input);
                break;

            case DataInputFactory::MCE_TEXTAREA:
                new MCETextArea($input);
                break;

            case DataInputFactory::DATE:
                new DateField($input);
                new DateInput($input);
                $input->setValidator(new DateValidator());
                break;

            case DataInputFactory::TIME:
                new TimeField($input);
                new TimeInput($input);
                $input->setValidator(new TimeValidator());
                break;

            case DataInputFactory::PHONE:
                new PhoneField($input);
                new PhoneInput($input);
                $input->setValidator(new PhoneValidator());
                break;


            case DataInputFactory::NESTED_SELECT:
                new NestedSelectField($input);
                break;

            case DataInputFactory::SLIDER:
                new SliderField($input);
                break;

            case DataInputFactory::SESSION_IMAGE:

                $input = new ArrayDataInput($name, $label, $required);

                new SessionImage($input);

                $processor = new SessionUploadInput($input);

                $validator = new ImageUploadValidator();
                $input->setValidator($validator);

                break;
            case DataInputFactory::SESSION_FILE:

                $input = new ArrayDataInput($name, $label, $required);

                new SessionFile($input);

                $processor = new SessionUploadInput($input);

                $validator = new FileUploadValidator();
                $input->setValidator($validator);

                break;

            case DataInputFactory::CAPTCHA_TEXT:

                $input = new DataInput($name, $label, $required);
                new TextCaptchaField($input);
                $input->setValidator(new TextCaptchaValidator());
                break;

            case DataInputFactory::CHECKBOX_TREEVIEW:

                $input = new ArrayDataInput($name, $label, $required);
                new CheckboxTreeView($input);
                break;

            default:
                throw new Exception("Unknown input type: " . $type);

        }
        return $input;

    }

}

?>
