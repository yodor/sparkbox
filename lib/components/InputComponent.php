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
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/Action.css";
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

    protected function syncAttrs(): void
    {
        parent::syncAttrs();

        $this->setAttribute("field", $this->dataInput->getName());

        if ($this->dataInput->isRequired()) {
            $this->setAttribute("required", 1);
        }
        else {
            $this->removeAttribute("required");
        }
    }

    protected function finalize(): void
    {
        parent::finalize(); // This triggers syncAttrs()

        // Update child component attributes
        $this->translator->setAttribute("field", $this->dataInput->getName());

        // Structural CSS changes
        if ($this->dataInput->getRenderer() instanceof HiddenField) {
            $this->addClassName("Hidden");
        }

        //the caption is a span, not a <label> - the input is named by its text for screen readers (a name set before
        //stays: the sort select of ResultViewHeader)
        $input = $this->dataInput->getRenderer()->input();
        if ($input instanceof Input && !($this->dataInput->getRenderer() instanceof HiddenField) && $input->getAttribute("aria-label") === "") {
            $caption = trim(html_entity_decode(strip_tags($this->dataInput->getLabel()), ENT_QUOTES | ENT_HTML5, "UTF-8"));
            if ($caption !== "") $input->setAttribute("aria-label", $caption);
        }

        // Logic for child component visibility
        if (Spark::GetBoolean(Config::TRANSLATOR_ENABLED) && $this->dataInput->translatorEnabled()) {
            $this->translator->setRenderEnabled(true);
        }
    }

}