<?php
include_once("components/Container.php");
include_once("components/ClosureComponent.php");
include_once("components/LabelSpan.php");
include_once("components/Action.php");

include_once("input/DataInput.php");
include_once("input/ArrayDataInput.php");

class InputComponent extends Container
{

    protected ?DataInput $dataInput = null;
    protected ?InputLabel $label_renderer = null;
    protected ?Action $translator = null;
    protected ?ClosureComponent $closure = null;

    public function __construct(?DataInput $input = NULL)
    {
        parent::__construct(false);

        $this->label_renderer = new InputLabel();
        $this->items()->append($this->label_renderer);

        $this->closure = new ClosureComponent(null, false);
        $this->items()->append($this->closure);

        $this->translator = new Action("TranslateBeanField");
        $this->translator->setContents("Translate");
        $this->translator->setRenderEnabled(false);
        $this->items()->append($this->translator);

        if ($input) {
            $this->setDataInput($input);
        }
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setDataInput(DataInput $input) : void
    {
        $this->dataInput = $input;
        $this->label_renderer->setDataInput($input);
        $this->closure->setClosure($this->dataInput->getRenderer()->render(...));
    }

    public function getDataInput(): ?DataInput
    {
        return $this->dataInput;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $this->setAttribute("field", $this->dataInput->getName());

        $this->translator->setAttribute("field", $this->dataInput->getName());

        if ($this->dataInput->isRequired()) {
            $this->setAttribute("required", 1);
        }
        else {
            $this->removeAttribute("required");
        }

        if ($this->dataInput->getRenderer() instanceof HiddenField) {
            $this->addClassName("Hidden");
        }


        if (TRANSLATOR_ENABLED && $this->dataInput->translatorEnabled()) {
            $this->translator->setRenderEnabled(true);
        }
    }

}

?>
