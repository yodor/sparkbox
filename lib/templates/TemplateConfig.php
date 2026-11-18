<?php
include_once("objects/SparkObject.php");

class TemplateConfigEvent extends SparkEvent
{
    const string UPDATE = "UPDATE";

}

/**
 * The configuration of a module path for its content - the context passed to the content: the content class,
 * title, description, authorization, the content settings (data) and an observer of the template events.
 * A path file creates it (TemplateConfig::Factory(), or the factory of the content: BeanList::Configure() ...); the
 * last one created is the active one. Contents with more settings have their own config class (BeanConfig).
 */
class TemplateConfig extends SparkObject
{

    protected static ?TemplateConfig $Active = null;

    /**
     * Transition wrappers - the content owns its config factory now (BeanList::Configure(), BeanEditor::Configure() ...)
     * @deprecated
     */
    public static function List(string $beanClass): BeanConfig
    {
        return BeanConfig::Content(BeanList::class, $beanClass);
    }

    /** @deprecated BeanTree::Configure() */
    public static function Tree(string $beanClass): BeanConfig
    {
        return BeanConfig::Content(BeanTree::class, $beanClass);
    }

    /** @deprecated BeanGallery::Configure() */
    public static function Gallery(string $beanClass): BeanConfig
    {
        return BeanConfig::Content(BeanGallery::class, $beanClass);
    }

    /** @deprecated BeanEditor::Configure() */
    public static function Editor(string $beanClass, string $formClass): BeanConfig
    {
        $config = BeanConfig::Content(BeanEditor::class, $beanClass);
        $config->formClass = $formClass;
        return $config;
    }

    /** @deprecated ConfigEditor::Configure() */
    public static function Config(string $section, string $formClass) : BeanConfig
    {
        $config = BeanConfig::Content(ConfigEditor::class);
        $config->configSection = $section;
        $config->formClass = $formClass;
        return $config;
    }

    /** @deprecated Plain::Configure() */
    public static function Plain(string $title, string $description) : TemplateConfig
    {
        $config = TemplateConfig::Content(Plain::class);
        $config->title = $title;
        $config->description = $description;
        return $config;
    }

    /**
     * A config of this class with the generic settings of '$other' - Template::LoadContent() converts the config
     * of a path to the config class of its content (TemplateContent::ConfigClass())
     * @param TemplateConfig $other
     * @return static
     */
    public static function From(TemplateConfig $other) : static
    {
        $config = static::Factory();
        $config->title = $other->title;
        $config->description = $other->description;
        $config->contentClass = $other->contentClass;
        $config->observer = $other->observer;
        $config->filename = $other->filename;
        $config->clearNavigation = $other->clearNavigation;
        $config->requireAuth = $other->requireAuth;
        $config->data = $other->data;
        return $config;
    }

    /**
     * A config of the content class '$contentClass' - the factories of the contents start with it
     * @param string $contentClass
     * @return static
     */
    public static function Content(string $contentClass) : static
    {
        $config = static::Factory();
        $config->contentClass = $contentClass;
        return $config;
    }

    /**
     * A new config - the active one (TemplateConfig::Active()); BeanConfig::Factory() a BeanConfig
     * @return static
     */
    public static function Factory() : static
    {
        return new static();
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
        $config->filename = "";
        $config->clearNavigation = false;
        $config->requireAuth = true;
    }

    /** @deprecated Login::Configure() */
    public static function Login() : TemplateConfig
    {
        $config = TemplateConfig::Content(Login::class);
        $config->requireAuth = false;
        $config->clearNavigation = true;
        return $config;
    }

    /** @deprecated Password::Configure() */
    public static function Password() : TemplateConfig
    {
        $config = TemplateConfig::Content(Password::class);
        $config->requireAuth = false;
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
            //Template::LoadContent() registers it for the TemplateEvents
            SparkEventManager::unregisterClosure(TemplateEvent::class, TemplateConfig::$Active->observer);
        }
        TemplateConfig::$Active = null;
    }

    public static function Active(): ?TemplateConfig
    {
        return TemplateConfig::$Active;
    }

    /**
     * Used to create the page caption contents fills page->setName
     * Important for building navigation history - same name replace the back history
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
     * Settings of the content set by the path file - the same content class on several paths
     * (ex. array("attribute" => "Author") - the authors index); the content documents its keys
     * @var array
     */
    public array $data = array();



    /**
     * Current configuration filename
     * @var string
     */
    public string $filename = "";

    /**
     * History navigation is reset. Used during switching of main menus
     * @var bool
     */
    public bool $clearNavigation = false;

    /**
     * Content requires authentication
     * @var bool
     */
    public bool $requireAuth = true;


}

include_once("templates/BeanConfig.php");
