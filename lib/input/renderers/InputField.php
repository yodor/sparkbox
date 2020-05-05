<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/IErrorRenderer.php");
include_once("lib/iterators/IDataIterator.php");

abstract class InputField extends Component implements IErrorRenderer
{

    /**
     * @var int
     */
    public $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    public static $value_na = "-";

    /**
     * @var DataInput
     */
    protected $input;

    /**
     * @var IDataIterator
     */
    protected $iterator = NULL;

    public $list_key = FALSE;
    public $list_label = FALSE;

    public $addon_content = "";

    protected $freetext_value = FALSE;

    protected $field_attributes = array();

    public function __construct(DataInput $input)
    {
        parent::__construct();
        $this->input = $input;

        $input->setRenderer($this);

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/InputField.css";
        return $arr;
    }

    public function setFieldAttribute($name, $value)
    {
        $this->field_attributes[$name] = $value;
        return $this;
    }

    public function getFieldAttribute($name)
    {
        return $this->field_attributes[$name];
    }

    public function enableFreetextInput($src_id)
    {
        $this->freetext_value = $src_id;
    }

    public function getFreetextInput()
    {
        return $this->freetext_value;
    }

    public function setIterator(IDataIterator $query)
    {
        $this->iterator = $query;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    public function processErrorAttributes()
    {

        $field_error = $this->input->getError();

        if (is_array($field_error)) $field_error = implode(";", $field_error);

        if (strlen($field_error) > 0) {

            if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
                $this->attributes["tooltip"] = $field_error;
            }
            $this->attributes["error"] = 1;
        }
        else {
            //$this->attributes["error"] = FALSE;
        }

    }

    public function prepareFieldAttributes()
    {
        if (!$this->input->isEditable()) {
            $this->setFieldAttribute("disabled", "true");
        }
        return $this->getAttributesText($this->field_attributes);

    }

    public function setInput(DataInput $input)
    {
        $this->input = $input;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function startRender()
    {
        $this->setFieldAttribute("name", $this->input->getName());

        //access attributes directly. allow sub components to override setAttribute
        $this->attributes["field"] = $this->input->getName();

        $this->processErrorAttributes();

        $field_error = $this->input->getError();

        if (is_array($field_error)) $field_error = implode(";", $field_error);

        if (strlen($field_error) > 0) {

            if ($this->error_render_mode == IErrorRenderer::MODE_SPAN) {
                echo "<span class='error_detail'>";
                echo $field_error;
                echo "</span>";
            }

        }
        parent::startRender();

        if (strlen($this->caption) > 0) {
            echo "<div class='caption'>";
            echo $this->caption;
            echo "</div>";
        }
    }

    public function finishRender()
    {

        $user_data = $this->input->getUserData();
        if (strlen($user_data) > 0) {
            echo "<div class='UserData'>";
            echo $user_data;
            echo "</div>";
        }

        if ($this->addon_content) {
            echo "<div class='addon_content'>";
            echo $this->addon_content;
            echo "</div>";
        }

        parent::finishRender();
    }

}

?>
