<?php
include_once("objects/SparkObject.php");
include_once("objects/events/TemplateEvent.php");

class TemplateConfigEvent extends SparkEvent
{
    const string UPDATE = "UPDATE";

}

/**
 * The configuration of a module path for its content - the context passed to the content: the content class,
 * title, description, authorization, the content settings (data) and an observer of the template events.
 * A path file creates it (the factory of its content: BeanList::Configure() ..., or TemplateConfig::Factory()); the
 * last one created is the active one. Contents with more settings have their own config class (BeanConfig).
 */
class TemplateConfig extends SparkObject
{

    protected static ?TemplateConfig $Active = null;

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
