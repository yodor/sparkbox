<?php
include_once("templates/TemplateContent.php");
include_once("beans/ConfigBean.php");

class ConfigEditor extends TemplateContent
{

    /**
     * @var ConfigFormProcessor
     */
    protected ConfigFormProcessor $processor;


    /**
     * @var InputForm|null
     */
    protected ?InputForm $form = null;


    public function __construct()
    {
        parent::__construct();

        $this->processor = new ConfigFormProcessor();

        $this->bean = ConfigBean::Factory();

        $this->processor->setBean($this->bean);
    }

    public function initialize(): void
    {
        // TODO: Implement initialize() method.
        $this->cmp = new FormRenderer($this->form);
    }

    public function setForm(InputForm $form): void
    {
        $this->form = $form;
    }

    public function getForm(): InputForm
    {
        return $this->form;
    }

    public function getProcessor(): FormProcessor
    {
        return $this->processor;
    }

    public function setSection(string $section): void
    {

        $this->bean->setSection($section);

    }

    public function processInput(): void
    {
        $this->processor->process($this->form);

        if ($this->processor->getStatus() == IFormProcessor::STATUS_OK) {
            Session::SetAlert("Configuration Updated");
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    public function formRenderer(): FormRenderer
    {
        if ($this->cmp instanceof FormRenderer) return $this->cmp;
        throw new Exception("Incorrect component class - expected FormRenderer");
    }

}