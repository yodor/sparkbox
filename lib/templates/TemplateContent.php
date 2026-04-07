<?php
include_once("objects/SparkObject.php");
include_once("utils/IRequestProcessor.php");
include_once("templates/TemplateConfig.php");


abstract class TemplateContent extends SparkObject implements IRequestProcessor
{
    protected ?TemplateConfig $config = null;
    protected ?Component $cmp = null;
    protected ?DBTableBean $bean = null;

    protected ?SelectQuery $query = null;

    protected bool $inputProcessed = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;

        //match reference bean from condition if set
        if (Template::Condition() && $this->config->useCondition) {
            //copy keyID
            $keyID = Template::Condition()->getID();
            $keyName = Template::Condition()->getBean()->key();

            Debug::ErrorLog("Using Template::Condition $keyName [$keyID] to match bean data");

            $this->bean->select()->where()->match($keyName , $keyID);
        }
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    /**
     * Create new Action object using Template::PathURL and URL::Current as sourceURL for its href.
     * $usePath can be relative or absolute (to the Template::ModuleLocation).
     * Returned url does not contain pagination parameters.
     * @param string $action
     * @param string|null $contents
     * @param string $usePath
     * @return Action
     * @throws Exception
     */
    public static function CreateAction(string $action, ?string $contents = "", string $usePath = ""): Action
    {
        $act = new Action();
        $act->setURL(Module::PathURL($usePath, URL::Current()));
        $act->getURL()->setClearPageParams(true);
        $act->setAction($action);
        //$act->getURL()->add(new URLParameter("action", $action));
        if (!is_null($contents)) {
            $act->setContents($contents);
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

    /**
     * Create instance of $config->beanClass using SparkLoader and set $this->bean to it
     * Set default title using getContentTitle and the bean class if is set
     *
     * @param TemplateConfig $config
     * @return void
     * @throws Exception
     */
    public function setup(TemplateConfig $config): void
    {

        $this->config = $config;

        if ($config->beanClass) {
            $this->setBean(SparkLoader::Factory("beans")->instance($config->beanClass, DBTableBean::class));
        }

        if (!$config->title) {
            $config->title = $this->getContentTitle();
            if (!is_null($this->bean)) $config->title .= ": " . get_class($this->bean);
        }

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