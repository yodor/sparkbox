<?php
include_once("objects/SparkObject.php");

class TemplateConfigEvent extends SparkEvent
{
    const string UPDATE = "UPDATE";

}

class TemplateConfig extends SparkObject
{

    protected static ?TemplateConfig $Active = null;

    public function __construct()
    {
        parent::__construct();
        TemplateConfig::Deactivate();
        TemplateConfig::$Active = $this;
    }

    public static function Deactivate() : void
    {
        if (!is_null(TemplateConfig::$Active) && TemplateConfig::$Active->observer) {
            Debug::ErrorLog("Removing previous TemplateConfig::observer");
            SparkEventManager::unregisterClosure(TemplateConfigEvent::class, TemplateConfig::$Active->observer);
        }
        TemplateConfig::$Active = null;
    }

    public static function Active(): ?TemplateConfig
    {
        return TemplateConfig::$Active;
    }

    /**
     * Used to create the page caption contents fills page->setName
     * @var string
     */
    public string $title = "";

    /**
     * Create instance of this class during content Template::Create()
     * @var string
     */
    public string $contentClass = "";

    /**
     * Informative text contents. For top level menu pages. Informative
     * @var string
     */
    public string $summary = "";

    /**
     * Create listener for TemplateEvents
     * @var Closure|null
     */
    public ?Closure $observer = null;

    /**
     * @var RequestParameterCondition|null
     */
    public ?RequestParameterCondition $condition = null;

    /**
     * DBTableBean class name for BeanList and BeanEditor
     * @var string
     */
    public string $beanClass = "";

    /**
     * InputForm class name for BeanEditor
     * @var string
     */
    public string $formClass = "";

    /**
     * Set the KeywordSearch columns
     * @var array|null
     */
    public ?array $searchField = null;

    /**
     * Used in TableView to set the visible columns
     * @var array|null
     */
    public ?array $listFields = null;

    /**
     * @var SQLQuery|null
     */
    public ?SQLQuery $iterator = null;

    /**
     * Current configuration filename
     * @var string
     */
    public string $filename = "";

    public bool $clearNavigation = false;
}