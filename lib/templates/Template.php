<?php
include_once("templates/TemplateConfig.php");
include_once("templates/TemplateContent.php");
include_once("objects/events/TemplateEvent.php");
include_once("objects/SparkObserver.php");
include_once("utils/RequestParameterCondition.php");
include_once("utils/BeanKeyCondition.php");

final class Template
{

    static private string $PrefixTemplateContent = "templates/content";

    static private string $PrefixTemplateHandlers = "templates/handlers";

    static private string $Module = "";

    static private string $ModulePathFolder = "path";

    static private ?TemplateConfig $Config = null;

    static private ?BeanKeyCondition $Condition = null;

    private function __construct()
    {

    }

    public static function PathURL(string $path) : URL
    {
        $url = new URL();
        $url->add(new URLParameter("path", $path));
        return $url;
    }

    public static function SetModule(string $module) : void
    {
        Template::$Module = $module;

    }

    public static function Condition(?BeanKeyCondition $condition = null) : ?BeanKeyCondition
    {
        if (!is_null($condition)) {
            Template::$Condition = $condition;
        }
        return Template::$Condition;
    }

    public static function ModuleLocation() : string
    {
        return Template::$PrefixTemplateHandlers.DIRECTORY_SEPARATOR.Template::$Module;
    }

    public static function WrapObserver(Closure $current, ?Closure $parent=null) : Closure
    {
        return function (...$args) use ($current, $parent) {
            if ($parent instanceof Closure) {
                $parent(...$args);
            }
            return $current(...$args);
        };
    }

    public static function List(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->beanClass = $beanClass;
        $config->contentClass = BeanList::class;
        return $config;
    }

    public static function Tree(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanTree::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    public static function Gallery(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanGallery::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    public static function Editor(string $beanClass, string $formClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanEditor::class;

        $config->beanClass = $beanClass;
        $config->formClass = $formClass;
        return $config;
    }

    public static function Plain(string $title, string $summary): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = Plain::class;
        $config->title = $title;
        $config->summary = $summary;

        return $config;
    }

    /**
     * Set/Get the active TemplateConfig configuration used to LoadContent
     *
     * @param TemplateConfig|null $config Set $config as the active TemplateConfig
     * @return TemplateConfig|null Return the current active TemplateConfig
     */
    public static function Config(?TemplateConfig $config=null): ?TemplateConfig
    {
        if (!is_null($config)) {

            //remove old observer
            if (!is_null(Template::$Config) && Template::$Config->observer) {
                Debug::ErrorLog("Removing previous TemplateConfig::observer");
                SparkEventManager::unregisterClosure(TemplateEvent::class, Template::$Config->observer);
            }

            Template::$Config = $config;
            SparkEventManager::emit(new TemplateConfigEvent(TemplateConfigEvent::UPDATE, Template::$Config));
        }

        return Template::$Config;
    }

    /**
     * Load content using the active TemplateConfig configuration
     * @return TemplateContent
     * @throws Exception
     */
    public static function LoadContent(): TemplateContent
    {

        if (!(Template::$Config instanceof TemplateConfig)) {
            throw new Exception("TemplateConfig not initialized yet");
        }

        if (!Template::$Config->contentClass) throw new Exception("TemplateConfig contentClass is empty");

        $cmp = SparkLoader::Factory(Template::$PrefixTemplateContent)->instance(Template::$Config->contentClass, TemplateContent::class);
        if (!($cmp instanceof TemplateContent)) throw new Exception("Content class not instance of TemplateContent");

        if (!is_null(Template::$Config->observer)) {
            SparkEventManager::register(TemplateEvent::class, new SparkObserver(Template::$Config->observer));
        }

        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_CREATED, $cmp));

        $cmp->setup(Template::$Config);
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_SETUP, $cmp));

        $cmp->initialize();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INITIALIZED, $cmp));

        $cmp->processInput();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INPUT_PROCESSED, $cmp));

        return $cmp;

    }

    /**
     * Search all loader enabled locations using prefix 'admin/path' and include path file that set the active configuration
     * @param string $path
     * @param int|null $editor
     * @return void
     * @throws Exception
     */
    public static function PathConfig(string $path) : void
    {

        Debug::ErrorLog("Using path: ".$path);

        $path = Spark::Split($path, "/");
        $path = implode(".", $path);

        $modulePath = Template::ModuleLocation();
        $modulePath.= DIRECTORY_SEPARATOR.Template::$ModulePathFolder;

        //search all include paths for code to call Template::Configure
        SparkLoader::Factory($modulePath)->include($path);

        if (!(Template::$Config instanceof TemplateConfig)) {
            throw new Exception("TemplateConfig not initialized after searching [$modulePath] for [$path.php]");
        }

        Template::$Config->filename = $path;



    }
}