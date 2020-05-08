<?php
include_once("components/Component.php");
include_once("input/renderers/IErrorRenderer.php");
include_once("iterators/IDataIterator.php");

/**
 * Class InputField
 * Base class to wraps various input tags into a Component
 */
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
     * Implementing classes use this iterator to render their contents
     * (DataSourceField)
     * @var IDataIterator
     */
    protected $iterator = NULL;

    public $list_key = FALSE;
    public $list_label = FALSE;

    public $addon_content = "";

    protected $freetext_value = FALSE;

    /**
     * Attributes to be used for the actual input element.
     * Separate collection from the Component attributes.
     * @var array
     */
    protected $input_attributes = array();

    public function __construct(DataInput $input)
    {
        parent::__construct();
        $this->input = $input;

        $input->setRenderer($this);

        $this->attributes["field"] = $this->input->getName();
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/InputField.css";
        return $arr;
    }

    public function setInputAttribute($name, $value)
    {
        $this->input_attributes[$name] = $value;
    }

    public function getInputAttribute($name)
    {
        return $this->input_attributes[$name];
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

    public function setInput(DataInput $input)
    {
        $this->input = $input;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function processErrorAttributes()
    {
        if (!$this->input->haveError()) return;

        if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
            $this->setAttribute("tooltip", tr($this->input->getError()));
        }
        $this->setAttribute("error", 1);
    }

    protected function prepareInputAttributes() : string
    {
        $this->setInputAttribute("name", $this->input->getName());

        if (!$this->input->isEditable()) {
            $this->setInputAttribute("disabled", "true");
        }
        return $this->getAttributesText($this->input_attributes);

    }

    public function startRender()
    {

        $this->processErrorAttributes();

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

        if ($this->input->haveError() && $this->error_render_mode == IErrorRenderer::MODE_SPAN) {
            echo "<small class='error_details'>";
            echo tr($this->input->getError());
            echo "</small>";
        }

        parent::finishRender();

    }

}

?>
