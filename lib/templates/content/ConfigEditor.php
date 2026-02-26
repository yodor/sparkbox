<?php


include_once("templates/TemplateContent.php");

include_once("beans/ConfigBean.php");

class ConfigEditor extends \templates\TemplateContent
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
        \templates\TemplateContent::__construct();

        $this->processor = new ConfigFormProcessor();

        $this->bean = ConfigBean::Factory();

        $this->processor->setBean($this->bean);
    }

    public function initialize(): void
    {
        // TODO: Implement initialize() method.
    }

    public function setForm(InputForm $form): void
    {
        $this->form = $form;

        $rend = new FormRenderer($form);

        $this->bean->loadForm($form);

    }

    public function getForm(): InputForm
    {
        return $this->form;
    }

    public function getProcessor(): FormProcessor
    {
        return $this->processor;
    }

    public function setConfigSection(string $section): void
    {

        $this->bean->setSection($section);

        if ($this->form) {
            $this->bean->loadForm($this->form);
        }

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


    public function component(): Component
    {
        return $this->form->getRenderer();
    }

}