<?php
include_once("templates/TemplateContent.php");
include_once("beans/ConfigBean.php");
include_once("forms/processors/ConfigFormProcessor.php");
include_once("forms/renderers/FormRenderer.php");

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

    public function setup(TemplateConfig $config): void
    {
        parent::setup($config);
        if ($config->configSection) {
            $this->setSection($config->configSection);
        }
        if ($config->formClass) {
            $this->setForm(SparkLoader::Factory(SparkLoader::PREFIX_FORMS)->instance($config->formClass, InputForm::class));
        }
    }

    public function processInput(): void
    {
        //current values of the section
        $this->bean->loadForm($this->form);

        $this->processor->process($this->form);

        if ($this->processor->getStatus() == IFormProcessor::STATUS_OK) {
            Session::SetAlert(tr("Configuration Updated"));
            //back to the same module path (PHP_SELF would be the module index.php)
            header("Location: " . Module::PathURL("", URL::Current()));
            exit;
        }
        else if ($this->processor->getStatus() == IFormProcessor::STATUS_ERROR) {
            Session::SetAlert(tr("Update failed") . ": " . $this->processor->getMessage());
        }
    }

    protected function getContentTitle(): string
    {
        return tr("Configuration");
    }

    public function formRenderer(): FormRenderer
    {
        if ($this->cmp instanceof FormRenderer) return $this->cmp;
        throw new Exception("Incorrect component class - expected FormRenderer");
    }

}