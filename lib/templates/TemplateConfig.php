<?php
include_once("objects/SparkObject.php");

class TemplateConfigEvent extends SparkEvent
{
    const string UPDATE = "UPDATE";

}

class TemplateConfig extends SparkObject
{

    protected static ?TemplateConfig $Active = null;

    public static function List(string $beanClass): TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->beanClass = $beanClass;
        $config->contentClass = BeanList::class;
        return $config;
    }

    public static function Tree(string $beanClass): TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = BeanTree::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    public static function Gallery(string $beanClass): TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = BeanGallery::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    /**
     * Create TemplateConfig for loading BeanEditor TemplateContent
     *
     * @param string $beanClass
     * @param string $formClass
     * @return TemplateConfig
     */
    public static function Editor(string $beanClass, string $formClass): TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = BeanEditor::class;

        $config->beanClass = $beanClass;
        $config->formClass = $formClass;
        return $config;
    }

    /**
     * Create TemplateConfig for loading Plain TemplateContent
     * @param string $title
     * @param string $description
     * @return TemplateConfig
     */
    public static function Plain(string $title, string $description) : TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = Plain::class;
        $config->title = $title;
        $config->description = $description;

        return $config;
    }

    public static function Factory() : TemplateConfig
    {
        return new TemplateConfig();
    }

    /**
     * Rest to defaults
     * @param TemplateConfig $config
     * @return void
     */
    private static function Initialize(TemplateConfig $config) : void
    {
        $config->title = "";
        $config->description = "";
        $config->contentClass = "";
        $config->observer = null;
        $config->beanClass = "";
        $config->formClass = "";
        $config->searchField = null;
        $config->listFields = null;
        $config->iterator = null;
        $config->filename = "";
        $config->clearNavigation = false;
        $config->requireAuth = true;
    }
    /**
     * Create TemplateConfig for loading Plain TemplateContent
     * @param string $title
     * @param string $description
     * @return TemplateConfig
     */
    public static function Login() : TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = Login::class;
        $config->requireAuth = false;
        $config->clearNavigation = true;
        return $config;
    }

    public static function Password() : TemplateConfig
    {
        $config = TemplateConfig::Factory();
        $config->contentClass = Password::class;
        $config->requireAuth = false;
        $config->clearNavigation = false;
        return $config;
    }

    public static function WrapObserver(Closure $current, ?Closure $parent = null): Closure
    {
        return function (...$args) use ($current, $parent) {
            if ($parent instanceof Closure) {
                $parent(...$args);
            }
            return $current(...$args);
        };
    }

    public function __construct()
    {
        parent::__construct();
        TemplateConfig::Deactivate();
        TemplateConfig::Initialize($this);
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
     * Informative text description. For top level menu pages or error pages
     * @var string
     */
    public string $description = "";

    /**
     * Create instance of this class during Template::LoadContent()
     * @var string
     */
    public string $contentClass = "";

    /**
     * Create listener for TemplateEvents
     * @var Closure|null
     */
    public ?Closure $observer = null;

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
     * @var SelectQuery|null
     */
    public ?SelectQuery $iterator = null;

    /**
     * Current configuration filename
     * @var string
     */
    public string $filename = "";

    public bool $clearNavigation = false;

    public bool $requireAuth = true;
}