<?php
include_once("components/Container.php");
include_once("input/renderers/IErrorRenderer.php");
include_once("components/renderers/IDataIteratorRenderer.php");

/**
 * Class InputField
 * Base class wrapping various input tags into a Component
 * Used to get visual representation of DataInput values
 */
abstract class InputField extends Container implements IErrorRenderer, IDataIteratorRenderer
{

    /**
     * @var DataIteratorItem|null
     */
    protected $item = NULL;

    /**
     * @var int
     */
    protected int $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    /**
     * @var DataInput
     */
    protected DataInput $dataInput;

    /**
     * Render values iterator
     * Implementing classes use this iterator to render their values using data from the iterator
     * (DataIteratorItem)
     * @var IDataIterator|null
     */
    protected ?IDataIterator $iterator = NULL;

    protected Container $addon_contents;


    protected bool $is_compound = false;

    protected ?Input $input = null;

    public function __construct(DataInput $dataInput)
    {
        parent::__construct(false);
        $this->setComponentClass("InputField");
        $this->setClassName(get_class($this));

        $this->addon_contents = new Container(false);
        $this->addon_contents->setClassName("addon");

        $this->dataInput = $dataInput;
        $dataInput->setRenderer($this);

        $this->input = $this->createInput();

        if ($this->input instanceof Input) {
            $this->items()->append($this->input);
        }
    }

    protected function createInput() : ?Input
    {
        return null;
    }

    public function input() : ?Input
    {
        return $this->input;
    }

    public function requiredStyle() : array
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
        $this->item->setValueKey($this->dataInput->getName());
        $this->item->setLabelKey($this->dataInput->getName());
    }

    public function getItemRenderer(): ?DataIteratorItem
    {
        return $this->item;
    }


    public function setDataInput(DataInput $input) : void
    {
        $this->dataInput = $input;
    }

    public function getDataInput() : DataInput
    {
        return $this->dataInput;
    }


    protected function processAttributes(): void
    {
        parent::processAttributes();

        $this->setAttribute("field", $this->dataInput->getName());

        $this->input?->setName($this->dataInput->getName());

        if (!$this->dataInput->isEditable()) {
            $this->input?->setAttribute("disabled", "true");
        }

        if ($this->addon_contents->items()->count()>0) {
            $this->items()->append($this->addon_contents);
        }

        $this->processErrorAttributes();


    }

    protected function processErrorAttributes() : void
    {
        if (!$this->dataInput->haveError()) return;

        $this->setAttribute("error", 1);

        $error = "";
        if ($this->dataInput instanceof ArrayDataInput) {
            $error = $this->dataInput->getErrorText();
        }
        else {
            $error = tr($this->dataInput->getError());
        }

        if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {

            if ($this->input) {
                $this->input->setTooltipText($error);
            }
            else {
                $this->setTooltipText($error);
            }

        }
        else if ($this->error_render_mode == IErrorRenderer::MODE_SPAN) {
            $error = new TextComponent($error);
            $error->setComponentClass("error_details");
            $error->setTagName("small");
            $this->items()->append($error);
        }


    }

    public function getAddonContainer() : Container
    {
        return $this->addon_contents;
    }

    public function setErrorRenderMode(int $mode) : void
    {
        $this->error_render_mode = $mode;
    }

    public function getErrorRenderMode(): int
    {
        return $this->error_render_mode;
    }
}

?>
