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

    static private string $PrefixTemplateModules = "templates/modules";

    static private string $Module = "";

    static private string $ModuleLocation = "";

    static private string $ModulePathFolder = "path";

    static private ?BeanKeyCondition $Condition = null;

    static private array $DisabledPaths = array();

    private function __construct()
    {}

    /**
     * Create/Convert url to 'path url' style - copying parameters from sourceURL if present.
     * If $path parameter is present it overwrites the source path parameter
     * If $path parameter is relative - path is appended to $sourceURL path
     * If $path parameter is absolute (starting with '/') - path is replaced with $path
     * Resulting url path is Template::$ModuleLocation + $path
     *
     * @param string $path
     * @param URL|null $sourceURL
     * @return URL
     */
    public static function PathURL(string $path, ?URL $sourceURL = null): URL
    {
        $path = rtrim($path, '/');

        $result = new URL();
        if (!is_null($sourceURL)) {
            $result = clone $sourceURL;
        }

        $pathParam = new URLParameter("path");
        if ($result->contains("path")) {
            $pathParam = $result->get("path");
        } else {
            $result->add($pathParam);
        }

        if (str_starts_with($path, "/")) {
            $pathParam->setValue($path);
        } else {
            $pathParam->setValue(rtrim($pathParam->value(), "/") . "/" . $path);
        }

        $script = Template::$ModuleLocation . rtrim($pathParam->value(), "/");
        $result->remove("path");
        $result->setScriptName($script);
        return $result;
    }

    public static function SetModule(string $module, string $location): void
    {
        Template::$Module = $module;
        Template::$ModuleLocation = rtrim($location, '/');
    }

    public static function Condition(?BeanKeyCondition $condition = null): ?BeanKeyCondition
    {
        if (!is_null($condition)) {
            Template::$Condition = $condition;
        }
        return Template::$Condition;
    }

    public static function ModuleLocation(): string
    {
        return Template::$PrefixTemplateModules . DIRECTORY_SEPARATOR . Template::$Module;
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

    public static function Plain(string $title, string $summary) : TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = Plain::class;
        $config->title = $title;
        $config->summary = $summary;

        return $config;
    }

    /**
     * Load content using the active TemplateConfig configuration
     * @return TemplateContent
     * @throws Exception
     */
    public static function LoadContent(): TemplateContent
    {

        $config = TemplateConfig::Active();
        if (is_null($config)) {
            throw new Exception("No active TemplateConfig");
        }

        if (!$config->contentClass) throw new Exception("TemplateConfig contentClass is empty");

        $cmp = SparkLoader::Factory(Template::$PrefixTemplateContent)->instance($config->contentClass, TemplateContent::class);
        if (!($cmp instanceof TemplateContent)) throw new Exception("Content class not instance of TemplateContent");

        //register here - allow modification during PathConfig inclusion
        if (!is_null($config->observer)) {
            SparkEventManager::register(TemplateEvent::class, new SparkObserver($config->observer));
        }

        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_CREATED, $cmp));

        $cmp->setup($config);
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_SETUP, $cmp));

        $cmp->initialize();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INITIALIZED, $cmp));

        $cmp->processInput();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INPUT_PROCESSED, $cmp));

        return $cmp;

    }

    public static function DisablePath(string $path): void
    {
        $path = rtrim(strtolower($path),"/");
        Debug::ErrorLog("Disabling path: $path");
        Template::$DisabledPaths[$path] = 1;
    }

    public static function DisabledPaths() : array
    {
        return array_keys(Template::$DisabledPaths);
    }

    private static function PathAccess(string $path) : void
    {
        Debug::ErrorLog("Checking path access for path: $path");

        foreach (Template::$DisabledPaths as $disablePath => $enabled) {
            if (str_starts_with($path, $disablePath)) {
                throw new Exception("PathAccess Denied");
            }
        }
    }

    /**
     * Includes '$path' file from all SparkLoader enabled locations using prefix Template::ModuleLocation()
     * Clears old TemplateConfig
     * Emits TemplateConfigEvent::UPDATE if Template::$Config is not null after
     * @param string $path
     * @return void
     * @throws Exception
     */
    public static function PathConfig(string $path) : void
    {
        Template::PathAccess($path);

        Debug::ErrorLog("Using path: ".$path);

        $path = Spark::Split($path, "/");
        $path = implode(".", $path);

        $modulePath = Template::ModuleLocation();
        $modulePath.= DIRECTORY_SEPARATOR.Template::$ModulePathFolder;

        TemplateConfig::Deactivate();

        //search all include paths for code to call Template::Configure
        SparkLoader::Factory($modulePath)->include($path, true);

        $config = TemplateConfig::Active();
        if (is_null($config)) {
            throw new Exception("TemplateConfig not initialized after searching [$modulePath] for [$path.php]");
        }

        $config->filename = $path;

        //Emit here after all including is done. Allow later included code to register for events or modify config
        SparkEventManager::emit(new TemplateConfigEvent(TemplateConfigEvent::UPDATE, $config));
    }

    public static function ErrorConfig(string $title, string $message): void
    {
        $config = Template::Plain($title, $message);
        //Emit here
        SparkEventManager::emit(new TemplateConfigEvent(TemplateConfigEvent::UPDATE, $config));
    }
}