<?php

class Module
{

    /**
     * Page class to use during Module::Response()
     * Can be overwritten from module init.php
     * @var string
     */
    public string $pageClass = "";

    /**
     * Can be overwritten from module init.php
     * Authenticator class to use during Module::Response()
     * @var string
     */
    public string $authClass = "";

    /**
     * SparkLoader prefix
     */
    const string PREFIX = "templates/modules";

    /**
     * Module init file name without prefix
     */
    const string INIT_NAME = "init";

    /**
     * Module path folder name
     */
    const string PATH_FOLDER = "path";

    const string DEFAULT_NAME = "default";

    /**
     * Module name reference
     * @var string
     */
    protected string $name = "";

    /**
     * Module ROOT location. Absolute path from DOCUMENT_ROOT
     * @var string
     */
    protected string $location = "";

    /**
     * Current active AuthContext after calling Authenticate
     * @var AuthContext|null
     */
    protected ?AuthContext $context = null;

    /**
     *  Current active Authenticator.
     */
    protected ?Authenticator $authenticator = null;

    /**
     * Paths reachable without authorization (login, password) - keys in Template::FormatPath() form "/login"
     * @var array<string, bool>
     */
    protected array $publicPaths = array();

    /**
     * Current active Module instance
     * @var Module|null
     */
    protected static ?Module $instance = null;

    /**
     * Set the instance name and location
     * Actual location is Spark::Get(Config::LOCAL) + $location
     * @param string $name Module name reference
     * @param string $location Relative path to Spark::Get(Config::LOCAL)
     */
    private function __construct(string $name, string $location)
    {
        $this->name = $name;
        $this->location = Spark::PathParts(Spark::Get(Config::LOCAL), $location);
    }

    /**
     * Return new 'factory' instance of Module using '$name' and '$location'
     * Actual location is Spark::Get(Config::LOCAL) + $location
     * @param string $name Module name reference
     * @param string $location Relative path to Spark::Get(Config::LOCAL)
     * @return Module
     */
    public static function Factory(string $name, string $location) : Module
    {
        return new Module($name, $location);
    }

    public static function Active() : Module
    {
        if (is_null(Module::$instance)) throw new Exception("Module is not initialized");
        return Module::$instance;
    }

    /**
     * Set Active() instance to this
     * Include all init.php relative to Module::Prefix()
     * @return void
     * @throws Exception
     */
    public function initialize() : void
    {
        Module::$instance = $this;
        SparkLoader::Factory(Module::Active()->getPreifx())->include(Module::INIT_NAME, true);
    }


    /**
     * Get the active module SparkLoader prefix:
     *
     * Module::PREFIX . "/" . Module::Active()->name
     *
     * ex: "template/modules" "/" "admin"
     *
     * @return string
     */
    public function getPreifx(): string
    {
        return Module::PREFIX . "/" . $this->name;
    }

    /**
     * Active module absolute path from DOCUMENT_ROOT
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Active Module name reference
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Declare paths reachable without authorization. All other paths redirect to 'login' before any
     * path file is included.
     * @param string ...$paths path names as used in URLs ("login", "password")
     * @return void
     */
    public function addPublicPath(string ...$paths) : void
    {
        foreach ($paths as $path) {
            $this->publicPaths[Template::FormatPath($path, "/", true)] = true;
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isPublicPath(string $path) : bool
    {
        return isset($this->publicPaths[Template::FormatPath($path, "/", true)]);
    }

    /**
     * Return the module AuthContext. Not null after successful authorize.
     * @return AuthContext|null
     */
    public function getAuthContext() : ?AuthContext
    {
        return $this->context;
    }

    /**
     * @return Authenticator|null
     */
    public function getAuthenticator() : ?Authenticator
    {
        return $this->authenticator;
    }

    /**
     * Authorize using the active module Authenticator class assigned to $authClass
     * @return void
     * @throws Exception
     */
    protected function authorize() : void
    {

        $this->context = null;
        $this->authenticator = null;

        if ($this->authClass) {

            $object = SparkLoader::Factory(SparkLoader::PREFIX_AUTH)->instance($this->authClass, Authenticator::class);
            if (!($object instanceof Authenticator)) throw new Exception("Object is not instance of Authenticator");
            $this->authenticator = $object;

            $authContext = $object->authorize();
            if ($authContext instanceof AuthContext) {
                $this->context = $authContext;
                Debug::ErrorLog("Authorization Successful");
            }
            else {
                Debug::ErrorLog("Authorization Failed");
            }

        }
        else {
            Debug::ErrorLog("Authorization skipped - no authClass set");
        }
    }


    /**
     * Process the request using the current active module:
     *  - authorize
     *  - redirect to 'login' before any path file runs if not authorized and the path is not public
     *  - create the page - path config, template content (creates its responders)
     *  - process the responders - redirects and JSON responses happen before any output
     *  - render
     */
    public static function Response() : void
    {
        $config = Module::Active();

        //authorize
        $config->authorize();

        if ($config->authClass && is_null($config->getAuthContext())) {
            $path = (string)($_GET["path"] ?? "");
            if (!$config->isPublicPath($path)) {
                Session::SetAlert("Authorization Required");
                $loginPage = Module::PathURL("login");
                Debug::ErrorLog("Authorization required for path '$path' - redirecting to login page: $loginPage");
                header("Location: " . $loginPage);
                exit;
            }
        }

        if ($config->pageClass) {

            $page = SparkLoader::Factory(SparkLoader::PREFIX_PAGES)->instance($config->pageClass, SparkPage::class);
            if ($page instanceof SparkPage) {
                //handle path parameters , load module content from module path folder
                $page->initialize();

                //responders created by the page and its content
                include_once("responders/RequestController.php");
                RequestController::Process();

                $page->render();
            }
            else {
                throw new Exception("Module pageClass object is not instance of SparkPage");
            }
        }
    }

    /**
     * 'pathify' url - transfer 'path' query parameter value to the URL path
     * Create/Convert url to 'path url' style - copying parameters from sourceURL if present.
     * If $path parameter is present it overwrites the source path parameter
     * If $path parameter is relative - path is appended to $sourceURL path
     * If $path parameter is absolute (starting with '/') - path is replaced with $path
     * Resulting url path is absolute Template::$ModuleLocation + $path
     *
     * @param string $path
     * @param URL|null $sourceURL
     * @return URL
     * @throws Exception
     */
    public static function PathURL(string $path, ?URL $sourceURL = null): URL
    {
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

        $pathValue = $path;
        if (!str_starts_with($path, "/")) {
            $pathValue = $pathParam->value(). "/" . $path;
        }
        //fix/reformat
        $pathParam->setValue(Spark::PathParts($pathValue));

        //absolute path from document root
        $script = Spark::PathParts(Module::Active()->getLocation(), $pathParam->value());
        $result->remove("path");
        $result->setScriptName($script);
        return $result;
    }
}