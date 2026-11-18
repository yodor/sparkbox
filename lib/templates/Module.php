<?php
include_once("utils/IRequestProcessor.php");

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
    const string SCRIPTS_FOLDER = "scripts";
    const string CONTENT_FOLDER = "content";

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
     * Optional access check of an authorized request: fn(string $path): bool - false redirects to 'home'.
     * Runs before early responders and before any path file. Set from the module init.php
     * (admin: limited access roles).
     * @var Closure|null
     */
    public ?Closure $accessCheck = null;

    /**
     * Alert shown at the login page when a path needs login (translated with tr()). Empty: no alert - for
     * modules where the login is a normal step (the checkout cashier). Set from the module init.php.
     * @var string
     */
    public string $loginAlert = "Authorization Required";

    /**
     * Module path a request without login is sent to (must be a public path). Set from the module init.php.
     * @var string
     */
    public string $loginPath = "login";

    /**
     * Page class per module path (addPagePath()) - path => array(class, file to include)
     * @var array<string, array>
     */
    protected array $pagePaths = array();

    /**
     * Old addresses of the module (addRedirect()) - path => module path
     * @var array<string, string>
     */
    protected array $redirects = array();

    /**
     * Paths answered by a script of their own (addScriptPath()) - path => file
     * @var array<string, string>
     */
    protected array $scriptPaths = array();

    /**
     * Paths answered as another path (addPathAlias()) - path key => module path
     * @var array<string, string>
     */
    protected array $pathAliases = array();

    /**
     * The module paths have no trailing slash: GET /authors/ answers 301 to /authors (the query stays) - for
     * modules of single pages (main). The module folder itself keeps its slash.
     * @var bool
     */
    public bool $noTrailingSlash = false;

    /**
     * The look of the module pages: css classes of the body and stylesheets (addPageClass, addPageCSS)
     * @var array<string>
     */
    protected array $pageClasses = array();
    protected array $pageCSS = array();
    protected array $pageMeta = array();

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

    /**
     * Entry point of a module folder (its index.php): create module '$name' located at the folder of the
     * requested script, include its init.php files and process the request.
     * The folder name is free - the module works the same from /admin2/ or /admin/.
     * @param string $name Module name - templates/modules/<name>
     * @return void
     * @throws Exception
     */
    public static function Run(string $name) : void
    {
        $folder = str_replace(chr(92), "/", dirname((string)($_SERVER["SCRIPT_NAME"] ?? "/")));
        $local = (string)Spark::Get(Config::LOCAL);
        if ($local !== "" && str_starts_with($folder, $local)) {
            $folder = substr($folder, strlen($local));
        }

        $module = Module::Factory($name, trim($folder, "/") . "/");
        $module->initialize();
        Module::Response();
    }

    /**
     * True when a module answers this request (Module::Run())
     */
    public static function HasActive() : bool
    {
        return !is_null(Module::$instance);
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
        Module::RegisterContentLoader();
        SparkLoader::Factory(Module::Active()->getPreifx())->include(Module::INIT_NAME, true);
    }

    /**
     * The content classes are loaded when used - a path file calls BeanList::Configure(...) without an include:
     * the content folder of the active module (site, store, core), else the shared contents (contentLoader())
     * @return void
     */
    protected static function RegisterContentLoader() : void
    {
        static $registered = false;
        if ($registered) return;
        $registered = true;

        spl_autoload_register(function (string $className) : void {
            if (!Module::HasActive() || !Spark::ValidClassName($className)) return;
            $file = Module::Active()->contentLoader($className)->find($className);
            if ($file !== "") include_once($file);
        });
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
     * Page class for a path of the module (and the paths below it) instead of pageClass - modules whose paths need
     * different pages (products: list, details). A page that is an IRequestProcessor does its own work:
     * initialize(), processInput(), render(); any other page is a frame for the content of the path.
     * @param string $path module path ("details")
     * @param string $pageClass
     * @param string $include file of the class (include path) - included when the path is requested
     * @return void
     */
    public function addPagePath(string $path, string $pageClass, string $include = "") : void
    {
        $this->pagePaths[Template::FormatPath($path, "/", true)] = array($pageClass, $include);
    }

    /**
     * Page class of '$path': the one of the path (or the nearest path above it) set by addPagePath(), else pageClass.
     * Its file is included.
     * @param string $path
     * @return string
     */
    public function pageClassFor(string $path) : string
    {
        $path = Template::FormatPath($this->resolvePath($path), "/", true);
        while (true) {
            if (isset($this->pagePaths[$path])) {
                [$class, $include] = $this->pagePaths[$path];
                if ($include !== "") include_once($include);
                return $class;
            }
            if ($path === "/" || $path === "") break;
            $path = rtrim(dirname($path), "/") ?: "/";
        }
        return $this->pageClass;
    }

    /**
     * The path '$path' is answered as the module path '$target' - its page class and its path file - without a
     * redirect: an old address whose content redirects itself in one step (details.php?prodID= → the product
     * address by the product's canonical 301)
     * @param string $path old path ("details.php")
     * @param string $target module path ("details")
     * @return void
     */
    public function addPathAlias(string $path, string $target) : void
    {
        $this->pathAliases[Template::FormatPath($path, "/", true)] = $target;
    }

    /**
     * The module path that answers '$path' (addPathAlias())
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path) : string
    {
        $key = Template::FormatPath($path, "/", true);
        return $this->pathAliases[$key] ?? $path;
    }

    /**
     * An old address of the module answers with 301 to '$target' (a module path, "" the module folder) - the query
     * of the request is kept
     * @param string $path old path ("list.php")
     * @param string $target
     * @return void
     */
    public function addRedirect(string $path, string $target) : void
    {
        $this->redirects[Template::FormatPath($path, "/", true)] = $target;
    }

    /**
     * A path answered by a plain script - it does all of its work (headers, page, output) like a script of the
     * site root did: the home of a site, a file (logo, sitemap.xml), a page script not converted to a content yet.
     *
     * By convention a path has its script in templates/modules/<module>/scripts/<path>.php - the site one, else the
     * store one, else the core one (one file runs, the most specific). The path keeps its dots: /sitemap.xml →
     * scripts/sitemap.xml.php; the module folder itself is 'home'. addScriptPath() maps a path to another file.
     *
     * The script runs after the redirects and the authorization (a public path in a module with login),
     * instead of the module page.
     * @param string $path module path ("contacts", "sitemap.xml")
     * @param string $file script file (include path)
     * @return void
     */
    public function addScriptPath(string $path, string $file) : void
    {
        $this->scriptPaths[Module::PathKey($path)] = $file;
    }

    /**
     * The script of a path: addScriptPath() or the scripts folder of the module - "" if none
     * @param string $path
     * @return string
     */
    public function scriptFor(string $path) : string
    {
        $key = Module::PathKey($path);
        if (isset($this->scriptPaths[$key])) return $this->scriptPaths[$key];

        $name = Module::ScriptName($path);
        if ($name === "") return "";
        return SparkLoader::Factory(Spark::PathParts($this->getPreifx(), Module::SCRIPTS_FOLDER))->find($name);
    }

    /**
     * The loader of the content class '$className': the content folder of this module
     * (templates/modules/<module>/content - site, plugins, store, core), else the shared contents
     * (templates/content: Plain, NotFound, the Bean* contents, ConfigEditor)
     * @param string $className
     * @return SparkLoader
     */
    public function contentLoader(string $className) : SparkLoader
    {
        $loader = SparkLoader::Factory(Spark::PathParts($this->getPreifx(), Module::CONTENT_FOLDER));
        if (Spark::ValidClassName($className) && $loader->find($className) !== "") return $loader;
        return SparkLoader::Factory(Template::PREFIX_CONTENT);
    }

    /**
     * Run the script of the path - true when the path has one
     * @param string $path
     * @return bool
     */
    protected function processScript(string $path) : bool
    {
        $file = $this->scriptFor($path);
        if ($file === "") return false;

        Debug::ErrorLog("Module script: $path -> $file");
        include_once($file);
        return true;
    }

    /**
     * The file name of a path in the scripts folder: lower case segments of letters, digits, '.', '-', '_'
     * ('Sitemap.xml' → 'sitemap.xml', 'a/b' → 'a/b'); '' is 'home'. "" for anything else.
     */
    protected static function ScriptName(string $path) : string
    {
        $path = strtolower(trim($path, "/"));
        if ($path === "") return "home";
        $segments = explode("/", $path);
        foreach ($segments as $segment) {
            if (!preg_match("/^[a-z0-9][a-z0-9._-]*$/", $segment) || str_contains($segment, "..")) return "";
        }
        return implode("/", $segments);
    }

    /**
     * 301 to the path without its trailing slash (noTrailingSlash)
     * @return void
     */
    protected function processTrailingSlash() : void
    {
        if (!$this->noTrailingSlash) return;
        if (!in_array($_SERVER["REQUEST_METHOD"] ?? "GET", array("GET", "HEAD"))) return;

        $requested = (string)($_SERVER["REQUEST_URI"] ?? "/");
        $parts = explode("?", $requested, 2);
        $path = $parts[0];
        if (!str_ends_with($path, "/") || rtrim($path, "/") === rtrim($this->location, "/")) return;

        $target = rtrim($path, "/") . (isset($parts[1]) ? "?" . $parts[1] : "");
        Debug::ErrorLog("Module trailing slash: $requested -> $target");
        header("Location: " . $target, true, 301);
        exit;
    }

    /**
     * The key of a path in the maps of the module - the module folder itself is 'home'
     */
    protected static function PathKey(string $path) : string
    {
        if (trim($path, "/") === "") $path = "home";
        return Template::FormatPath($path, "/", true);
    }

    /**
     * Answer an old address (addRedirect()) with 301 - before anything else of the request
     * @param string $path
     * @return void
     */
    protected function processRedirect(string $path) : void
    {
        $key = Template::FormatPath($path, "/", true);
        if (!isset($this->redirects[$key])) return;

        $target = $this->redirects[$key];
        $url = ($target === "") ? new URL(rtrim($this->location, "/") . "/") : Module::PathURL($target);
        foreach ($_GET as $name => $value) {
            if ($name === "path" || !is_scalar($value)) continue;
            $url->add(new URLParameter((string)$name, (string)$value));
        }
        Debug::ErrorLog("Module redirect: $path -> " . $url->toString());
        header("Location: " . $url->toString(), true, 301);
        exit;
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
     * Result of the access check for '$path' - true without an access check
     * @param string $path
     * @return bool
     */
    public function canAccess(string $path) : bool
    {
        if (is_null($this->accessCheck)) return true;
        return (bool)($this->accessCheck)($path);
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
     * Answer a request for any other address of the page with a 301 to '$canonical' - one URL per page
     * (wrong or old slugs, a missing trailing slash, the script address of a slug page).
     * Only the path is compared; the query string of the request is kept, except parameters that
     * '$canonical' already carries (in its path or query). GET and HEAD requests only, no JSON requests.
     * @param URL $canonical
     * @return void
     */
    public static function RedirectCanonical(URL $canonical) : void
    {
        if (!in_array($_SERVER["REQUEST_METHOD"] ?? "GET", array("GET", "HEAD"), true)) return;
        include_once("responders/RequestController.php");
        if (RequestController::isJSONRequest()) return;

        $requested = URL::Requested();
        $target = new URL($canonical->toString());
        if (strcmp(rawurldecode($requested->getScriptName()), rawurldecode($target->getScriptName())) === 0) return;

        foreach ($requested->getParameterNames() as $name) {
            if ($canonical->contains($name) || $target->contains($name)) continue;
            $target->add($requested->get($name));
        }

        Debug::ErrorLog("Canonical redirect: " . $requested->toString() . " -> " . $target->toString());
        header("Location: " . $target->toString(), true, 301);
        exit;
    }

    /**
     * The ID of the authorized user of this request (the module authClass) - -1 when not authorized
     * @return int
     */
    /**
     * The module path of this request in the module format ('/orders/details'); 'home' for the module folder itself
     * @return string
     */
    public function getPath() : string
    {
        $path = $this->resolvePath((string)($_GET["path"] ?? ""));
        if (trim($path, "/") === "") return "home";
        return Template::FormatPath($path, "/", true);
    }

    /**
     * A css class of the body of every page of this module (the module look - set in its init.php)
     * @param string $className
     * @return void
     */
    public function addPageClass(string $className) : void
    {
        $this->pageClasses[] = $className;
    }

    /**
     * A stylesheet of every page of this module (the module look - set in its init.php)
     * @param string $href
     * @return void
     */
    public function addPageCSS(string $href) : void
    {
        $this->pageCSS[] = $href;
    }

    /**
     * A meta tag of every page of this module (the module look - set in its init.php; replaces the page default)
     * @param string $name
     * @param string $content
     * @return void
     */
    public function addPageMeta(string $name, string $content) : void
    {
        $this->pageMeta[$name] = $content;
    }

    /**
     * The content of the module path for the frame '$page': the path config (Template::PathConfig - 404 for an
     * unknown path, an error page for a failing one) and its content (Template::LoadContent: setup, initialize,
     * processInput)
     * @param SparkPage $page
     * @return TemplateContent
     * @throws Exception
     */
    protected function loadContent(SparkPage $page) : TemplateContent
    {
        $path = $this->getPath();
        try {
            Template::PathConfig($path);
            $page->body()->setAttribute("path", $path);
        }
        catch (TemplatePathNotFound $e) {
            Debug::ErrorLog("PathConfig: " . $e->getMessage());
            http_response_code(404);
            $page->head()->addMeta("robots", "noindex");
            Template::NotFoundConfig();
        }
        catch (Exception $e) {
            Debug::ErrorLog("PathConfig failed: " . $e->getMessage());
            $message = $e->getMessage();
            if (Debug::IsEnabled()) $message .= " - " . $e->getTraceAsString();
            Template::ErrorConfig("Error:$path", $message);
        }
        return Template::LoadContent();
    }

    public function getUserID() : int
    {
        return ($this->context instanceof AuthContext) ? $this->context->getID() : -1;
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

        //old addresses first
        $config->processRedirect((string)($_GET["path"] ?? ""));

        //addresses without a trailing slash (main module): /authors/ → /authors
        $config->processTrailingSlash();

        //authorize
        $config->authorize();

        if ($config->authClass && is_null($config->getAuthContext())) {
            $path = (string)($_GET["path"] ?? "");
            if (!$config->isPublicPath($path)) {
                $config->loginRedirect("path '$path'");
            }
        }

        if (!is_null($config->getAuthContext()) && !$config->canAccess((string)($_GET["path"] ?? ""))) {
            Session::SetAlert(tr("Access denied"));
            Debug::ErrorLog("Access denied by the module access check");
            header("Location: " . Module::PathURL("home"));
            exit;
        }

        //responders that do not need the page answer here - the page is not created at all
        if (!is_null($config->getAuthContext())) {
            $config->processEarlyResponder();
        }

        //paths with a script of their own - after the authorization, like a page
        if ($config->processScript((string)($_GET["path"] ?? ""))) return;

        $pageClass = $config->pageClassFor((string)($_GET["path"] ?? ""));
        if ($pageClass) {

            $page = SparkLoader::Factory(SparkLoader::PREFIX_PAGES)->instance($pageClass, SparkPage::class);
            if ($page instanceof SparkPage) {
                //the module look
                foreach ($config->pageClasses as $className) $page->body()->addClassName($className);
                foreach ($config->pageCSS as $href) $page->head()->addCSS($href);
                foreach ($config->pageMeta as $name => $content) $page->head()->addMeta($name, $content);

                $page->initialize();

                if ($page instanceof IRequestProcessor) {
                    //a page that answers the path itself (the product pages)
                    $page->processInput();
                }
                else {
                    //the frame gets the content of the path
                    $page->setContent($config->loadContent($page));
                }

                //the request: responders created by the page and its content, the session alert, session closed
                include_once("responders/RequestController.php");
                RequestController::Respond();

                $page->render();
            }
            else {
                throw new Exception("Module pageClass object is not instance of SparkPage");
            }
        }
    }

    /**
     * Redirect to the module loginPath (with the loginAlert, if any) and stop
     * @param string $reason for the log
     * @return never
     */
    public function loginRedirect(string $reason) : never
    {
        if ($this->loginAlert !== "") Session::SetAlert(tr($this->loginAlert));
        $loginPage = Module::PathURL($this->loginPath);
        Debug::ErrorLog("Authorization required for $reason - redirecting to login page: $loginPage");
        header("Location: " . $loginPage);
        exit;
    }

    /**
     * JSON request for a responder with RequestResponder::EarlyProcessing() = true: create it by name
     * (responders/json/<name>.php in any location) and process it. Returns if the request is not for such
     * a responder - it is then processed normally after the page is created.
     * @return void
     * @throws Exception
     */
    protected function processEarlyResponder() : void
    {
        include_once("responders/RequestController.php");

        if (!RequestController::isJSONRequest() || !RequestController::isResponderRequest()) return;

        $name = (string)$_REQUEST[RequestResponder::KEY_COMMAND];
        if (!Spark::ValidClassName($name)) return;

        $loader = SparkLoader::Factory("responders/json");
        if ($loader->find($name) === "") return;
        $loader->define($name);

        if (!is_a($name, JSONResponder::class, true) || !$name::EarlyProcessing()) return;

        Debug::ErrorLog("Early processing: $name");
        $loader->instance($name, JSONResponder::class);
        //JSON request - exits after the response
        RequestController::Process();
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