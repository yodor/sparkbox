<?php
include_once("objects/SparkObject.php");
include_once("utils/IRequestProcessor.php");
include_once("templates/TemplateConfig.php");


abstract class TemplateContent extends SparkObject implements IRequestProcessor
{
    protected ?TemplateConfig $config = null;
    protected ?Component $cmp = null;
    protected ?RequestParameterCondition $request_condition = null;
    protected ?DBTableBean $bean = null;

    protected ?SQLQuery $query = null;

    protected bool $inputProcessed = false;

    public function __construct()
    {
        parent::__construct();

    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public static function CreateAction(string $action, ?string $contents = "", string $appendPath = ""): Action
    {
        $act = new Action();
        $act->setURL(SparkTemplateAdminPage::Instance()->currentURL());
        $act->setAction($action);
        $act->getURL()->add(new URLParameter("action", $action));
        if (!is_null($contents)) {
            $act->setContents($contents);
        }
        if ($appendPath) {
            $pathParam = $act->getURL()->get("path");
            if ($pathParam instanceof URLParameter) {
                $pathParam->setValue($pathParam->value() . "/" . $appendPath);
            }
        }
        return $act;
    }

    /**
     * Initialize the main view component
     * @return void
     */
    abstract public function initialize(): void;


    public function processInput(): void
    {
        $this->inputProcessed = true;
    }

    public function isProcessed(): bool
    {
        return $this->inputProcessed;
    }

    /**
     * Fill the required actions
     */
    public function fillPageActions(ActionCollection $collection): void
    {

    }

    public function fillPageFilters(Container $filters): void
    {

    }

    public function setRequestCondition(RequestParameterCondition $condition): void
    {
        $this->request_condition = $condition;
    }

    public function getRequestCondition(): RequestParameterCondition
    {
        return $this->request_condition;
    }

    public function setup(TemplateConfig $config): void
    {

        if ($config->beanClass) {
            $this->setBean(SparkLoader::Factory("beans")->instance($config->beanClass, DBTableBean::class));
        }
        if ($config->condition) {
            $this->setRequestCondition($config->condition);
        }

        if (!$config->title) {
            $config->title = $this->getContentTitle();
            if (!is_null($this->bean)) $config->title .= ": " . get_class($this->bean);
        }

        $this->config = $config;

    }

    public function config(): TemplateConfig
    {
        return $this->config;
    }

    protected function getContentTitle(): string
    {
        return "Item";
    }

}