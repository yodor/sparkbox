<?php
include_once("components/templates/admin/AdminPageTemplate.php");
include_once("forms/processors/ConfigFormProcessor.php");
include_once("forms/renderers/FormRenderer.php");

include_once("beans/ConfigBean.php");

class ConfigEditorPage extends AdminPageTemplate
{

    /**
     * @var ConfigFormProcessor
     */
    protected ConfigFormProcessor $processor;


    /**
     * @var InputForm|null
     */
    protected ?InputForm $form = null;

    /**
     * @var ConfigBean
     */
    protected ConfigBean $config;

    public function __construct()
    {
        parent::__construct();

        $this->processor = new ConfigFormProcessor();

        $this->config = ConfigBean::Factory();
        $this->processor->setBean($this->config);
    }

    public function setForm(InputForm $form) : void
    {
        $this->form = $form;

        $rend = new FormRenderer($form);

        $this->config->loadForm($form);

    }

    public function getForm() : InputForm
    {
        return $this->form;
    }

    public function getProcessor() : FormProcessor
    {
        return $this->processor;
    }

    public function setConfigSection(string $section) : void
    {

        $this->config->setSection($section);

        if ($this->form) {
            $this->config->loadForm($this->form);
        }

    }

    public function processInput() : void
    {
        $this->processor->process($this->form);

        if ($this->processor->getStatus() == IFormProcessor::STATUS_OK) {
            Session::SetAlert("Configuration Updated");
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    protected function initPageActions(): void
    {
        // TODO: Implement initPageActions() method.
    }

    public function initView(): ?Component
    {
        return $this->form->getRenderer();
    }

    protected function renderImpl(): void
    {
        $this->form->getRenderer()->render();
    }

}