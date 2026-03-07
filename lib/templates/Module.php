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
    protected ?AuthContext $auth = null;

    /**
     * Current active Module instance
     * @var Module|null
     */
    protected static ?Module $instance = null;

    private function __construct(string $name, string $location)
    {
        Module::$instance = $this;
        $this->name = $name;
        $this->location = $location;
    }

    public static function Factory(string $name, string $location) : Module
    {
        return new Module($name, $location);
    }

    public static function Active() : ?Module
    {
        return Module::$instance;
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
    public static function Prefix(): string
    {
        return Module::PREFIX . "/" . Module::Active()->name;
    }

    /**
     * Active module absolute path from DOCUMENT_ROOT
     * @return string
     */
    public static function Location(): string
    {
        $config = Module::Active();
        if (is_null($config)) throw new Exception("No active Module");

        return $config->location;
    }

    /**
     * Active Module name reference
     * @return string
     */
    public static function Name(): string
    {
        $config = Module::Active();
        if (is_null($config)) throw new Exception("No active Module");

        return $config->name;

    }

    /**
     * Include all init.php relative to Module::Prefix()
     * @return void
     * @throws Exception
     */
    public static function Initialize() : void
    {
        SparkLoader::Factory(Module::Prefix())->include(Module::INIT_NAME, true);
    }

    /**
     * Authorize using the active module Authenticator class assigned to $authClass
     * @return void
     * @throws Exception
     */
    public static function Authorize() : void
    {
        $config = Module::Active();
        if (is_null($config)) throw new Exception("No active Module");

        if ($config->authClass) {
            try {
                $object = SparkLoader::Factory(SparkLoader::PREFIX_AUTH)->instance($config->authClass, Authenticator::class);
                if (!($object instanceof Authenticator)) throw new Exception("Object is not instance of Authenticator");
                $authContext = $object->authorize();
                if (!($authContext instanceof AuthContext)) throw new Exception("Authorization failed");
                $config->auth = $authContext;

            } catch (Exception $e) {
                //redirect login
                $loginPage = Spark::PathParts($config->location, "login.php");
                Debug::ErrorLog("--- Redirecting to login page: $loginPage");
                Session::setAlert($e->getMessage());
                header("Location: $loginPage");
                exit;
            }
        }
        else {
            Debug::ErrorLog("Authorization skipped - no authClass set");
        }
    }

    /**
     * Return the active module current AuthContext. Available after calling Authorize
     * @return AuthContext|null
     * @throws Exception
     */
    public static function AuthContext() : ?AuthContext
    {
        $config = Module::Active();
        if (is_null($config)) throw new Exception("No active Module");
        return $config->auth;
    }

    /**
     * Process the request using the current active module
     * Calls RequestController::ProcessDynamic()
     * Loads and initializes and renders the pageClass assigned
     */
    public static function Response() : void
    {
        $config = Module::Active();
        if (is_null($config)) throw new Exception("No active Module");

        try {
            include_once("responders/RequestController.php");
            RequestController::ProcessDynamic();
        }
        catch (Exception $e) {
            Session::setAlert($e->getMessage());
        }

        if ($config->pageClass) {
            $page = SparkLoader::Factory(SparkLoader::PREFIX_PAGES)->instance($config->pageClass, SparkPage::class);
            if ($page instanceof SparkPage) {
                //handle path parameters , load module content from module path folder
                $page->initialize();
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
        $script = Spark::PathParts(Module::Location(), $pathParam->value());
        $result->remove("path");
        $result->setScriptName($script);
        return $result;
    }
}