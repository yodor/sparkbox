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

    const string MODULE_PATH_FOLDER = "path";

    static private ?BeanKeyCondition $Condition = null;

    static private array $DisabledPaths = array();

    private function __construct()
    {
    }

    public static function AsciiOnly(string $value): string
    {
        return preg_replace('/[^A-Za-z]/', '', $value);
    }
    /**
     * Reformat or clean path string
     * ex with separator '.' : /some/module/path -> some.module.path
     * ex with separator '/' : //some/..//module/ -> /some/module
     *
     * @param string $path Path string ex '/some/module/option'
     * @param string $separator Separator char to use in reformating
     * @param bool $prependRoot Prepend the separator char to the result
     * @return string
     */
    public static function FormatPath(string $path, string $implodeSeparator, bool $prependRoot): string
    {

        $path = strtolower($path);
        $path = str_replace(".", "", $path);
        $path = str_replace("..", "", $path);

        $parts = Spark::Split($path, "/");
        //filter ascii only
        array_walk($parts, function (string &$value, string|int $key): void {
            $value = Template::AsciiOnly($value);
        });

        $path = implode($implodeSeparator, $parts);

        if ($prependRoot) {
            $path = $implodeSeparator.$path;
        }

        return $path;
    }

    /**
     * 'pathify' url - transfer 'path' query parameter value to the URL path
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

        $script = Template::$ModuleLocation . DIRECTORY_SEPARATOR. Template::FormatPath($pathParam->value(), "/", false);
        $result->remove("path");
        $result->setScriptName($script);
        return $result;
    }

    /**
     * Set the active module
     * @param string $module Module name
     * @param string $location Module access location in document root
     * @return void
     */
    public static function SetModule(string $module, string $location): void
    {
        Template::$Module = Template::AsciiOnly(trim($module, ' /'));
        Template::$ModuleLocation = rtrim($location, '/');
    }

    public static function Condition(?BeanKeyCondition $condition = null): ?BeanKeyCondition
    {
        if (!is_null($condition)) {
            Template::$Condition = $condition;
        }
        return Template::$Condition;
    }

    /**
     * Get the active module location.
     * Template::$PrefixTemplateModules."/".Template::$Module
     * ex: template/modules/<module_name>
     *
     * @return string
     */
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



    /**
     * Using the TemplateConfig::Active()
     * Create instance of TemplateContent using config->contentClass.
     * Calls setup, initialize, processInput, emitting corresponding events.
     * Registers listener using config->observer.
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

    /**
     * Append path to the disabled paths array
     * @param string $path
     * @return void
     */
    public static function DisablePath(string $path): void
    {
        $path = rtrim(strtolower($path),"/");
        Debug::ErrorLog("Disabling path: $path");
        Template::$DisabledPaths[$path] = 1;
    }

    /**
     * Return the current disabled paths array values
     * @return array
     */
    public static function DisabledPaths() : array
    {
        return array_keys(Template::$DisabledPaths);
    }

    /**
     * Check Template::DisabledPaths() array for presence
     *
     * @param string $path
     * @return void
     * @throws Exception Throws if path is present in the disabled paths
     */
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
     * Calls TemplateConfig::Deactivate() to clear any old config
     * Emits TemplateConfigEvent::UPDATE if TemplateConfig::Active() is not null
     * @param string $path
     * @return void
     * @throws Exception Throws if TemplateConfig::Active() is null
     */
    public static function PathConfig(string $path) : void
    {
        Template::PathAccess($path);

        Debug::ErrorLog("Using path: ".$path);

        //convert //some/path/ to some.path
        $path = Template::FormatPath($path, ".", false);

        $moduleLocation = Spark::PathParts(Template::ModuleLocation(),Template::MODULE_PATH_FOLDER);

        TemplateConfig::Deactivate();

        //search all include locations for a code that instantiate a TemplateConfig object
        SparkLoader::Factory($moduleLocation)->include($path, true);

        //TemplateConfig CTOR set a static instance accessible using the TemplateConfig::Active() method
        $config = TemplateConfig::Active();
        if (is_null($config)) {
            throw new Exception("TemplateConfig not initialized after searching [$moduleLocation] for [$path.php]");
        }

        $config->filename = $path;

        //Emit here after all including is done. Allow later included code to register for events or modify config
        SparkEventManager::emit(new TemplateConfigEvent(TemplateConfigEvent::UPDATE, $config));
    }

    public static function ErrorConfig(string $title, string $message): void
    {
        $config = TemplateConfig::Plain($title, $message);
        //Emit here
        SparkEventManager::emit(new TemplateConfigEvent(TemplateConfigEvent::UPDATE, $config));
    }

    public static function ModuleInit(ModuleConfig $config, string $initName="init") : void
    {
        Template::SetModule($config->name, $config->location);

        //search all include locations for a code that instantiate a TemplateConfig object
        SparkLoader::Factory(Template::ModuleLocation())->include($initName, true);
    }

    public static function ModuleResponse() : void
    {

        $config = ModuleConfig::Active();
        if (is_null($config)) throw new Exception("No active ModuleConfig");
        if ($config->authClass) {
            try {
                $object = SparkLoader::Factory("auth")->instance($config->authClass, Authenticator::class);
                if (!($object instanceof Authenticator)) throw new Exception("Result not instance of Authenticator");
                $config->authContext = $object->authorize();
                if (!($config->authContext instanceof AuthContext)) throw new Exception("Authorization failed");

            } catch (Exception $e) {
                //redirect login
                Session::setAlert($e->getMessage());
                header("Location: ".Template::ModuleLocation()."/login.php");
                exit;
            }
        }
        try {
            include_once("responders/RequestController.php");
            RequestController::ProcessDynamic();
        }
        catch (Exception $e) {
            Session::setAlert($e->getMessage());
        }

        $page = SparkLoader::Factory("pages")->instance($config->pageClass, SparkTemplatePage::class);
        if (!($page instanceof SparkTemplatePage)) throw new Exception("Object is not instance of SparkTemplatePage");

        $page->initialize();
        $page->render();

    }
}