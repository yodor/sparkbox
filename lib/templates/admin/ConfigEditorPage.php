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
    protected $processor;

    /**
     * @var InputForm
     */
    protected $form;

    /**
     * @var ConfigBean
     */
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->processor = new ConfigFormProcessor();

    }

    public function setForm(InputForm $form)
    {
        $this->form = $form;

        $rend = new FormRenderer($form);

        $rend->setClassName("config_form");

        if ($this->config) {
            $this->config->loadForm($form);
        }
    }

    public function setConfigSection(string $section)
    {
        $this->config = ConfigBean::Factory();
        $this->config->setSection($section);

        $this->processor->setBean($this->config);

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

    protected function renderImpl()
    {
        $this->form->getRenderer()->render();
    }

}