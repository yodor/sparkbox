<?php
include_once("utils/IRequestProcessor.php");
include_once("templates/Template.php");
include_once("pages/SparkPage.php");

class Module
{

    /**
     * Page class to use during Module::Response() - the frame of the contents of the module paths.
     * Empty: a module without a frame - every path answers its content as a fragment (ContentFragmentResponder).
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
     * The site init of every module request: templates/init.php of each layer (core, store, the site's class/),
     * before the init.php of the module - the setup of the site that does not belong to a frame (the products
     * query, the cart discount, the default item renderers ...)
     */
    const string SITE_PREFIX = "templates";

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
     * Paths reachable without authorization (login, password) - keys in Module::PathKey() form "/login"
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
     * Addresses with values in them (addPattern()) - pattern => array(module path, parameter names)
     * @var array<string, array>
     */
    protected array $patterns = array();

    /**
     * The modules of the site by their address folder (AddRoute(), config/routes.php) - folder => module name
     * @var array<string, string>
     */
    protected static array $routes = array();

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
     * The module of the address folder '$folder' ('admin' - /admin/...) is '$name' (default: the folder name).
     * Called by the site config/routes.php - the root index.php (Route()) answers the addresses of the modules.
     * @param string $folder
     * @param string $name
     * @return void
     */
    public static function AddRoute(string $folder, string $name = "") : void
    {
        $folder = trim($folder, "/");
        if ($folder === "" || str_contains($folder, "/")) throw new Exception("Module route: one folder name - '$folder'");
        Module::$routes[$folder] = ($name !== "") ? $name : $folder;
    }

    /**
     * The modules of the site by their address folder (AddRoute)
     * @return array<string, string>
     */
    public static function Routes() : array
    {
        return Module::$routes;
    }

    /**
     * Entry point of the site root (its index.php - the front controller): the first folder of the address picks
     * the module of the site config/routes.php (AddRoute), anything else is the main module. The request is then
     * the same as through a module folder of its own (/admin/index.php?path=/settings) - SCRIPT_NAME, ?path= -
     * and the module runs (Run()). A module folder of the site still works on its own (the server serves it before
     * the root rewrite).
     * @return void
     * @throws Exception
     */
    public static function Route() : void
    {
        $routes = APP_PATH . "/config/routes.php";
        if (is_file($routes)) include_once($routes);

        $local = rtrim((string)Spark::Get(Config::LOCAL), "/");
        $uri = (string)($_SERVER["REQUEST_URI"] ?? "/");
        $uriPath = rawurldecode((string)(parse_url($uri, PHP_URL_PATH) ?? "/"));
        $query = (string)(parse_url($uri, PHP_URL_QUERY) ?? "");
        if ($local !== "" && str_starts_with($uriPath, $local)) $uriPath = substr($uriPath, strlen($local));
        if ($uriPath === "") $uriPath = "/";

        $name = "main";
        $folder = "";
        $rest = $uriPath;
        $parts = explode("/", ltrim($uriPath, "/"), 2);
        if ($parts[0] !== "" && isset(Module::$routes[$parts[0]])) {
            $folder = $parts[0];
            $name = Module::$routes[$folder];
            //the module folder itself: with its slash (as the server does for a folder)
            if (count($parts) < 2) {
                header("Location: " . $local . "/" . $folder . "/" . (($query !== "") ? "?" . $query : ""), true, 301);
                exit;
            }
            $rest = "/" . $parts[1];
        }

        //the parameters of the request as PHP read them (the root rewrite adds ?path=/<address> in front of the
        //client query) - only the path is set again: from the address, or the client's own ?path= for the folder
        //itself or its index.php (/admin/index.php?path=/settings)
        $get = $_GET;
        unset($get["path"]);
        $client = array();
        parse_str($query, $client);
        if ($rest === "/" || $rest === "/index.php") {
            if (isset($client["path"]) && is_string($client["path"])) $get["path"] = $client["path"];
        }
        else {
            $get["path"] = $rest;
        }

        $_GET = $get;
        $_REQUEST = array_merge($get, $_POST);
        $_SERVER["QUERY_STRING"] = http_build_query($get, "", "&", PHP_QUERY_RFC3986);
        $_SERVER["SCRIPT_NAME"] = $local . (($folder !== "") ? "/" . $folder : "") . "/index.php";
        $_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"];

        Module::Run($name);
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
        Module::SiteInit();
        SparkLoader::Factory(Module::Active()->getPrefix())->include(Module::INIT_NAME, true);
    }

    /**
     * The site init: templates/init.php of every layer, core first, the site last - once per request
     * @return void
     * @throws Exception
     */
    public static function SiteInit() : void
    {
        static $done = false;
        if ($done) return;
        $done = true;
        SparkLoader::Factory(Module::SITE_PREFIX)->include(Module::INIT_NAME, true);
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
    public function getPrefix(): string
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
            $this->publicPaths[Module::PathKey($path)] = true;
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
        $this->pagePaths[Module::PathKey($path)] = array($pageClass, $include);
    }

    /**
     * Page class of '$path': the one of the path (or the nearest path above it) set by addPagePath(), else pageClass.
     * Its file is included.
     * @param string $path
     * @return string
     */
    public function pageClassFor(string $path) : string
    {
        $path = Module::PathKey($this->resolvePath($path));
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
        $this->pathAliases[Module::PathKey($path)] = $target;
    }

    /**
     * The module path that answers '$path' (addPathAlias())
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path) : string
    {
        $key = Module::PathKey($path);
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
        $this->redirects[Module::PathKey($path)] = $target;
    }

    /**
     * An address with values in it: '$pattern' (a regular expression of the module path without its leading slash,
     * anchored at both ends) answers as the module path '$path', its groups become the request parameters '$names'
     * in their order - addPattern("([0-9]+)(/.*)?", "view", "dpID"): /pages/12/about/ → path /view, dpID=12.
     * Applied before anything else of the request. (The rewrite rules of the module .htaccess did this.)
     * @param string $pattern
     * @param string $path
     * @param string ...$names
     * @return void
     */
    public function addPattern(string $pattern, string $path, string ...$names) : void
    {
        $this->patterns[$pattern] = array($path, $names);
    }

    /**
     * The module path of '$path' after the patterns (addPattern) - their values set as request parameters
     * @param string $path
     * @return string
     */
    protected function processPatterns(string $path) : string
    {
        $subject = ltrim($path, "/");
        foreach ($this->patterns as $pattern => [$target, $names]) {
            if (!preg_match("#^(?:" . $pattern . ")$#i", $subject, $matches)) continue;
            foreach ($names as $index => $name) {
                if (!isset($matches[$index + 1])) continue;
                $_GET[$name] = $matches[$index + 1];
                $_REQUEST[$name] = $matches[$index + 1];
            }
            $target = "/" . ltrim($target, "/");
            $_GET["path"] = $target;
            $_REQUEST["path"] = $target;
            Debug::ErrorLog("Module pattern: $path -> $target");
            return $target;
        }
        return $path;
    }

    /**
     * A path answered by a plain script - it does all of its work (headers, page, output) like a script of the
     * site root did: the home of a site, a file (logo, sitemap.xml).
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
        return SparkLoader::Factory(Spark::PathParts($this->getPrefix(), Module::SCRIPTS_FOLDER))->find($name);
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
        $loader = SparkLoader::Factory(Spark::PathParts($this->getPrefix(), Module::CONTENT_FOLDER));
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
     * The path of the request as the module .htaccess passed it (?path=) - not normalized
     * @return string
     */
    public static function RequestPath() : string
    {
        return is_string($_GET["path"] ?? null) ? $_GET["path"] : "";
    }

    /**
     * The key of a path in the maps of the module ('/orders/details') - the module folder itself is '/home'
     * @param string $path
     * @return string
     */
    public static function PathKey(string $path) : string
    {
        if (trim($path, "/") === "") $path = "home";
        return Module::FormatPath($path, "/", true);
    }

    /**
     * A module path in the form of '$separator': lower case, the segments of letters only (anything else is dropped -
     * no dots, no '..'): '/Orders/Details/' → 'orders.details' ('.' - the path file), '/orders/details' ('/' with the
     * root - the keys), 'orders_details' ('_' - the body class)
     * @param string $path
     * @param string $separator
     * @param bool $prependRoot the separator before the result
     * @return string
     */
    public static function FormatPath(string $path, string $separator, bool $prependRoot) : string
    {
        $parts = array();
        foreach (Spark::Split(strtolower($path), "/") as $part) {
            $part = Spark::AsciiLetters($part);
            if ($part !== "") $parts[] = $part;
        }
        return ($prependRoot ? $separator : "") . implode($separator, $parts);
    }

    /**
     * Answer an old address (addRedirect()) with 301 - before anything else of the request
     * @param string $path
     * @return void
     */
    protected function processRedirect(string $path) : void
    {
        $key = Module::PathKey($path);
        if (!isset($this->redirects[$key])) return;

        $target = $this->redirects[$key];
        $url = ($target === "") ? new URL(rtrim($this->location, "/") . "/") : Module::PathURL($target);
        //a target with a trailing slash keeps it (a folder like address of the module: section/1/software/)
        if ($target !== "" && str_ends_with($target, "/")) $url->setScriptName(rtrim($url->getScriptName(), "/") . "/");
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
        return isset($this->publicPaths[Module::PathKey($path)]);
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
     * The module path of this request in the module format ('/orders/details'); 'home' for the module folder itself
     * @return string
     */
    public function getPath() : string
    {
        $path = $this->resolvePath(Module::RequestPath());
        if (trim($path, "/") === "") return "home";
        return Module::FormatPath($path, "/", true);
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
     * unknown path, an error page for a failing path file or content) and its content (Template::LoadContent: setup, initialize,
     * processInput)
     * @param SparkPage $page
     * @return TemplateContent
     * @throws Exception
     */
    protected function loadContent(?SparkPage $page) : TemplateContent
    {
        $path = $this->getPath();
        try {
            Template::PathConfig($path);
            $page?->body()->setAttribute("path", $path);
        }
        catch (TemplatePathNotFound $e) {
            Debug::ErrorLog("PathConfig: " . $e->getMessage());
            http_response_code(404);
            Module::NoIndex($page);
            Template::NotFoundConfig();
        }
        catch (Exception $e) {
            Debug::ErrorLog("PathConfig failed: " . $e->getMessage());
            $message = $e->getMessage();
            if (Debug::IsEnabled()) $message .= " - " . $e->getTraceAsString();
            //the error content shows it as text (it can carry the requested values)
            Template::ErrorConfig("Error:$path", htmlspecialchars($message));
        }

        try {
            return Template::LoadContent();
        }
        catch (Exception $e) {
            //a content that fails (an unknown row ...) - the error content of the path instead of an uncaught error
            Debug::ErrorLog("Content failed: " . $e->getMessage());
            Module::NoIndex($page);
            $message = $e->getMessage();
            if (Debug::IsEnabled()) $message .= " - " . $e->getTraceAsString();
            //the error content shows it as text (it can carry the requested values)
            Template::ErrorConfig("Error:$path", htmlspecialchars($message));
            return Template::LoadContent();
        }
    }

    /**
     * The ID of the authorized user of this request (the module authClass) - -1 when not authorized
     * @return int
     */
    /**
     * Not indexed: the robots meta of the page - the X-Robots-Tag header without a page (a fragment)
     * @param SparkPage|null $page
     * @return void
     */
    protected static function NoIndex(?SparkPage $page) : void
    {
        if ($page instanceof SparkPage) {
            $page->head()->addMeta("robots", "noindex");
        }
        else if (!headers_sent()) {
            header("X-Robots-Tag: noindex");
        }
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

        //the path of the request - every step below works on it (addresses with values first: addPattern)
        $path = $config->processPatterns(Module::RequestPath());

        $config->processRedirect($path);

        //addresses without a trailing slash (main module): /authors/ → /authors
        $config->processTrailingSlash();

        //authorize
        $config->authorize();

        if ($config->authClass && is_null($config->getAuthContext())) {
            if (!$config->isPublicPath($path)) {
                $config->loginRedirect("path '$path'");
            }
        }

        if (!is_null($config->getAuthContext()) && !$config->canAccess($path)) {
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
        if ($config->processScript($path)) return;

        include_once("responders/json/ContentFragmentResponder.php");
        //a fragment asked for (JSON): the head and the body collect what its components need
        $fragment = ContentFragmentResponder::IsRequested();
        if ($fragment) ContentFragmentResponder::CollectHead();

        $pageClass = $config->pageClassFor($path);
        if (!$pageClass) {
            //a module without a frame - the content of the path is the answer
            $config->respondFragment();
            return;
        }

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
                //the content alone, built in its frame (the frame gives it its context) - answered as JSON
                if ($fragment) new ContentFragmentResponder($page->getContent());
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

    /**
     * The answer of a module without a frame (pageClass empty): the content of the path as a fragment - JSON for a
     * JSON request (ContentFragmentResponder), else the HTML of the content with the stylesheets and scripts its
     * components need. Not indexed.
     * @return void
     * @throws Exception
     */
    protected function respondFragment() : void
    {
        ContentFragmentResponder::CollectHead();
        Module::NoIndex(null);
        //the module look: its stylesheets
        foreach ($this->pageCSS as $href) HTMLHead::Instance()->addCSS($href);

        $content = $this->loadContent(null);
        if (ContentFragmentResponder::IsRequested()) new ContentFragmentResponder($content);

        //JSON responders answer and exit, forms redirect
        include_once("responders/RequestController.php");
        RequestController::Respond();

        ContentFragmentResponder::RenderHTML($content);
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
     * Resulting url path is absolute: the location of the active module + $path
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