<?php
include_once("components/Container.php");
include_once("input/renderers/IErrorRenderer.php");
include_once("components/renderers/IDataIteratorRenderer.php");

/**
 * Class InputField
 * Base class wrapping various input tags into a Component
 */
abstract class InputField extends Component implements IErrorRenderer, IDataIteratorRenderer
{

    /**
     * @var DataIteratorItem|null
     */
    protected $item = NULL;

    /**
     * @var int
     */
    protected $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    /**
     * @var DataInput
     */
    protected $input;

    /**
     * Render values iterator
     * Implementing classes use this iterator to render their values using data from the iterator
     * (DataIteratorItem)
     * @var IDataIterator
     */
    protected $iterator = NULL;


    protected $addon_contents;

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

        $this->addon_contents = new Container();
        $this->addon_contents->setClassName("addon");
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/InputField.css";
        return $arr;
    }

    public function setIterator(IDataIterator $query)
    {
        $this->iterator = $query;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    public function setItemRenderer(DataIteratorItem $item)
    {
        $this->item = $item;
        $this->item->setValueKey($this->input->getName());
        $this->item->setLabelKey($this->input->getName());
    }

    public function getItemRenderer(): ?DataIteratorItem
    {
        return $this->item;
    }

    public function setInputAttribute(string $name, string $value)
    {
        $this->input_attributes[$name] = $value;
    }

    public function getInputAttribute(string $name): string
    {
        return $this->input_attributes[$name];
    }

    public function getInputAttributes(): array
    {
        return $this->input_attributes;
    }

    public function setInput(DataInput $input)
    {
        $this->input = $input;
    }

    public function getInput()
    {
        return $this->input;
    }

    /**
     * Set all input attributes before rendering is started.
     * subclasses use this method to set all attributes to be used on
     * the actual input element
     *
     */
    protected function processInputAttributes()
    {
        $this->setInputAttribute("name", $this->input->getName());

        if (!$this->input->isEditable()) {
            $this->setInputAttribute("disabled", "true");
        }
    }

    public function processErrorAttributes()
    {
        if (!$this->input->haveError()) return;

        if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {

            $error = $this->input->getError();
            if ($this->input instanceof ArrayDataInput) {
                $error = ArrayDataInput::ERROR_TEXT;
            }
            $this->setAttribute("tooltip", tr($error));

        }
        $this->setAttribute("error", 1);
    }

    /**
     * Subclasses that use the input attributes call this method to
     * get all the attributes to be used on the actual input field as text
     * @return string
     */
    protected function prepareInputAttributes(): string
    {

        return $this->getAttributesText($this->input_attributes);

    }

    public function startRender()
    {
        $this->processInputAttributes();
        $this->processErrorAttributes();

        parent::startRender();

    }

    public function finishRender()
    {

        if ($this->addon_contents->count()>0) {
            $this->addon_contents->render();
        }

        if ($this->input->haveError() && $this->error_render_mode == IErrorRenderer::MODE_SPAN) {
            echo "<small class='error_details'>";
            echo tr($this->input->getError());
            echo "</small>";
        }

        parent::finishRender();

    }

    public function getAddonContainer() : Container
    {
        return $this->addon_contents;
    }

    public function setErrorRenderMode(int $mode)
    {
        $this->error_render_mode = $mode;
    }

    public function getErrorRenderMode(): int
    {
        return $this->error_render_mode;
    }
}

?>
