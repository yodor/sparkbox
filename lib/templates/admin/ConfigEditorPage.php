<?php
include_once("templates/admin/AdminPageTemplate.php");
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
     * @var InputForm
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

    public function setForm(InputForm $form)
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

    public function setConfigSection(string $section)
    {

        $this->config->setSection($section);

        if ($this->form) {
            $this->config->loadForm($this->form);
        }

    }

    public function processInput()
    {
        $this->processor->process($this->form);

        if ($this->processor->getStatus() == IFormProcessor::STATUS_OK) {
            Session::SetAlert("Configuration Updated");
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    protected function initPageActions()
    {
        // TODO: Implement initPageActions() method.
    }

    public function initView()
    {
        // TODO: Implement initView() method.
    }

    protected function renderImpl(): void
    {
        $this->form->getRenderer()->render();
    }

}
