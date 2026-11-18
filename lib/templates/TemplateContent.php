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
        if (Template::Condition() && $this->config instanceof BeanConfig && $this->config->useCondition) {
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
     * The page finalized its head (title, description) - the content adds what depends on them
     * (ex. the prev / next links and the page number of a paged list: AbstractResultView::AppendHeadLinks).
     * Called by SparkPage::headFinalize() of the frame it is in.
     * @param SparkPage $page
     * @return void
     */
    public function finalizeHead(SparkPage $page): void
    {

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
     * The config of this content; a BeanConfig: create its beanClass (SparkLoader) as $this->bean
     * Set default title using getContentTitle and the bean class if is set
     *
     * @param TemplateConfig $config
     * @return void
     * @throws Exception
     */
    public function setup(TemplateConfig $config): void
    {

        $this->config = $config;

        if ($config instanceof BeanConfig && $config->beanClass) {
            $this->setBean(SparkLoader::Factory("beans")->instance($config->beanClass, DBTableBean::class));
        }

        if (!$config->title) {
            $config->title = $this->getContentTitle();
            if (!is_null($this->bean)) $config->title .= ": " . get_class($this->bean);
        }

    }

    /**
     * The config of this content for a path file - a new active config of ConfigClass() with this content class.
     * Contents with settings override it with optional parameters and call parent::Configure() first
     * (BeanList::Configure($beanClass), BeanEditor::Configure($beanClass, $formClass), Plain::Configure($title ...)).
     * The module loads its content classes itself - a path file needs no include.
     * @return TemplateConfig
     */
    public static function Configure() : TemplateConfig
    {
        $configClass = static::ConfigClass();
        return $configClass::Content(static::class);
    }

    /**
     * The config class of this content - a content with more settings than TemplateConfig names its own
     * (BeanConfig); Template::LoadContent() converts the config of the path to it
     * @return string
     */
    public static function ConfigClass() : string
    {
        return TemplateConfig::class;
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