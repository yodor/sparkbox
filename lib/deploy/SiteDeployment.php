<?php

/**
 * The deployment of a site of the core layer: a new site in an empty folder (create) or the SparkBox part of an
 * existing site root again (update). Run from tools/deploy.php of the layer (the store extends it: StoreDeployment).
 *
 * The description of the site - title, folder, database, root administrator, theme, language - comes from the
 * arguments of tools/deploy.php; the manifest of a site (prompt to site) is the saved form of the same description.
 *
 * create: a core site - sparkboot.php, the front controller index.php, the SparkBox block of .htaccess, config/
 * (boot, settings, dbconfig with --db, routes), the frame of the site (class/pages/<Name>Page.php on SparkSitePage,
 * the init.php of its modules), css/site.css (the site layer), robots.txt, .gitignore, the links of the public files
 * (sparkfront); with --db the database is enabled and --admin creates the root administrator (userID 1 - it can not
 * be deleted, suspended or limited).
 *
 * update: the SparkBox part of an existing root - the .htaccess block (the site's own lines stay), the front
 * controller, config/routes.php from the module links when there is none, the module links removed
 * (--remove-links), the public file links. A dry run unless --write.
 */
class SiteDeployment
{
    //the description of the site --------------------------------------------------------------------------------
    public string $title = "";
    public string $folder = "";
    public string $dbName = "";
    public string $dbPass = "";
    public string $dbHost = "localhost";
    public string $dbPort = "3306";
    public string $adminEmail = "";
    public string $adminPass = "";
    public string $theme = "light";
    //two-letter (ISO 639-1) - the locale follows it unless --locale is given
    public string $languageCode = "en";
    public string $locale = "en-us";
    protected bool $localeGiven = false;
    public string $timezone = "Europe/Sofia";

    //the paths as the web server sees them (default: this checkout, the folder)
    public string $sparkboxPath = "";
    public string $appPath = "";

    //update: a dry run unless write; remove the module links; also a site whose root is not the main module
    public bool $update = false;
    public bool $write = false;
    public bool $removeLinks = false;
    public bool $force = false;

    protected int $changes = 0;
    protected int $warnings = 0;

    public function __construct()
    {
        $this->sparkboxPath = (string)realpath(__DIR__ . "/../..");
    }

    /**
     * The name of the layer in the messages and the usage
     */
    protected function layer() : string
    {
        return "sparkbox";
    }

    public function usage() : string
    {
        $tool = $this->layer() . "/tools/deploy.php";
        return "Usage:\n"
            . "  php $tool --title=\"Site Title\" --folder=<empty folder> [--db=<name>:<password>] [--dbhost=<host>[:<port>]]\n"
            . "        [--admin=<email>:<password>] [--theme=light] [--language=en] [--locale=en-us]\n"
            . "        [--timezone=Europe/Sofia] [--sparkbox=<path>] [--app=<path>]\n"
            . "      a new site in an empty (or new) folder - the database name is also its user\n"
            . "  php $tool --update --folder=<site root> [--write] [--remove-links] [--force] [--sparkbox=<path>] [--app=<path>]\n"
            . "      the SparkBox part of an existing site root again - a dry run without --write\n";
    }

    /**
     * The description from the command line arguments
     * @param array $args
     * @return void
     * @throws Exception
     */
    public function parseArguments(array $args) : void
    {
        foreach ($args as $arg) {
            if (!preg_match("/^--([a-z-]+)(?:=(.*))?$/s", $arg, $m)) throw new Exception("Unknown argument: $arg");
            $name = $m[1];
            $value = $m[2] ?? null;
            $this->argument($name, $value);
        }
        if ($this->folder === "") throw new Exception("--folder is required");
        if (!$this->update && trim($this->title) === "") throw new Exception("--title is required");
        if ($this->adminEmail !== "" && $this->dbName === "") throw new Exception("--admin needs --db (the administrators are in the database)");
    }

    /**
     * One argument - a layer adds its own and calls this for the rest
     * @throws Exception
     */
    protected function argument(string $name, ?string $value) : void
    {
        $pair = function (?string $value, string $what) : array {
            $parts = explode(":", (string)$value, 2);
            if (count($parts) < 2 || $parts[0] === "" || $parts[1] === "") throw new Exception("--$what=<a>:<b> expected");
            return $parts;
        };
        switch ($name) {
            case "title": $this->title = (string)$value; break;
            case "folder": $this->folder = rtrim((string)$value, "/"); break;
            case "db": [$this->dbName, $this->dbPass] = $pair($value, "db"); break;
            case "dbhost":
                $parts = explode(":", (string)$value, 2);
                $this->dbHost = $parts[0];
                if (isset($parts[1])) $this->dbPort = $parts[1];
                break;
            case "admin":
                [$this->adminEmail, $this->adminPass] = $pair($value, "admin");
                if (!filter_var($this->adminEmail, FILTER_VALIDATE_EMAIL)) throw new Exception("--admin: not an email address");
                break;
            case "theme": $this->theme = (string)$value; break;
            case "language":
                //the two-letter code ('bg'; the earlier '<name>:<code>' form is understood)
                $parts = explode(":", (string)$value);
                $this->languageCode = strtolower(trim((string)end($parts)));
                if (!preg_match("/^[a-z]{2}$/", $this->languageCode)) throw new Exception("--language: a two-letter code (bg)");
                if (!$this->localeGiven) $this->locale = $this->languageCode . "-" . $this->languageCode;
                break;
            case "locale": $this->locale = (string)$value; $this->localeGiven = true; break;
            case "timezone": $this->timezone = (string)$value; break;
            case "sparkbox": $this->sparkboxPath = rtrim((string)$value, "/"); break;
            case "app": $this->appPath = rtrim((string)$value, "/"); break;
            case "update": $this->update = true; break;
            case "write": $this->write = true; break;
            case "remove-links": $this->removeLinks = true; break;
            case "force": $this->force = true; break;
            default: throw new Exception("Unknown argument: --$name");
        }
    }

    /**
     * create or update - false when it stopped
     * @return bool
     */
    public function run() : bool
    {
        try {
            return $this->update ? $this->updateSite() : $this->createSite();
        }
        catch (Exception $e) {
            $this->say("\nStopped: " . $e->getMessage());
            return false;
        }
    }

    //output ----------------------------------------------------------------------------------------------------------

    protected function say(string $text) : void
    {
        echo $text . "\n";
    }

    protected function warn(string $text) : void
    {
        $this->warnings++;
        echo "  ! " . $text . "\n";
    }

    /**
     * Write '$content' to '$file' - in update mode only with --write, keeping a copy of the old file
     */
    protected function put(string $file, string $content) : void
    {
        $this->changes++;
        if ($this->update && !$this->write) return;
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        if ($this->update && is_file($file)) copy($file, $file . ".before-deploy");
        if (file_put_contents($file, $content) === false) throw new Exception("Unable to write $file");
    }

    //create ----------------------------------------------------------------------------------------------------------

    /**
     * A new site in an empty (or new) folder
     * @return bool
     * @throws Exception
     */
    protected function createSite() : bool
    {
        $this->write = true;
        if (file_exists($this->folder)) {
            if (!is_dir($this->folder)) throw new Exception("'$this->folder' is not a folder");
            $entries = array_diff(scandir($this->folder), array(".", ".."));
            if (count($entries) > 0) throw new Exception("'$this->folder' is not empty - a new site goes into an empty folder (--update for an existing site)");
        }
        else if (!mkdir($this->folder, 0755, true)) {
            throw new Exception("Unable to create '$this->folder'");
        }
        $this->folder = (string)realpath($this->folder);
        if ($this->appPath === "") $this->appPath = $this->folder;

        $this->say("New " . $this->siteKind() . ": '$this->title' in $this->folder");
        $this->say("  " . $this->pathsLine());

        foreach ($this->siteFiles() as $file => $content) {
            $this->put($this->folder . "/" . $file, $content);
            $this->say("  $file");
        }

        $this->say("\nLinks");
        foreach ($this->frontLinks() as $name => $target) $this->link($target, $this->folder . "/" . $name);

        if ($this->dbName !== "") {
            $this->say("\nDatabase '$this->dbName'");
            $this->database();
        }

        $this->say("\nDone" . (($this->warnings > 0) ? " - $this->warnings warning(s) above" : "") . ". Next:");
        $this->say("  - the web root of the domain to this folder (public_html as a link: ln -s $this->folder <public_html>)");
        $this->say("  - git init in the folder (.gitignore keeps config/dbconfig.php and the links out)");
        if ($this->adminEmail !== "") $this->say("  - the admin: /admin/ with $this->adminEmail");
        return true;
    }

    /**
     * The paths of the deployment (as the web server sees them)
     */
    protected function pathsLine() : string
    {
        return "sparkbox: $this->sparkboxPath    app path: $this->appPath";
    }

    /**
     * "core site" / "store site"
     */
    protected function siteKind() : string
    {
        return "core site";
    }

    /**
     * The files of a new site: path => content
     * @return array<string, string>
     */
    protected function siteFiles() : array
    {
        $files = array();
        $files["sparkboot.php"] = (string)file_get_contents($this->sparkboxPath . "/dist/sparkboot.php.dist");
        $files["index.php"] = (string)file_get_contents($this->sparkboxPath . "/modules/main/index.php");
        $files[".htaccess"] = $this->htaccessBlock() . "\n";
        $files["robots.txt"] = (string)file_get_contents($this->sparkboxPath . "/dist/robots.txt.dist");
        $files[".gitignore"] = implode("\n", $this->gitIgnore()) . "\n";
        $files["config/boot.php"] = (string)file_get_contents($this->sparkboxPath . "/dist/boot.php.dist");
        $files["config/settings.php"] = $this->settingsFile();
        if ($this->dbName !== "") $files["config/dbconfig.php"] = $this->dbconfigFile();
        $files["config/routes.php"] = $this->routesFile();
        foreach ($this->frameFiles() as $file => $content) $files[$file] = $content;
        $files["css/site.css"] = $this->siteCss();
        return $files;
    }

    /**
     * The frame of the site: its page class (on SparkSitePage) and the init.php of its modules that set it
     * @return array<string, string>
     */
    protected function frameFiles() : array
    {
        $page = $this->pageClassName();
        $files = array("class/pages/$page.php" => $this->pageClassFile($page));
        foreach ($this->moduleInits($page) as $module => $content) {
            $files["class/templates/modules/$module/init.php"] = $content;
        }
        return $files;
    }

    protected function gitIgnore() : array
    {
        $lines = array("config/dbconfig.php", "config/mailer.php", "*.before-deploy");
        foreach (array_keys($this->frontLinks()) as $link) $lines[] = "/" . $link;
        return $lines;
    }

    /**
     * The modules of a new site (config/routes.php) - without a database only the main module
     * @return array
     */
    protected function routes() : array
    {
        if ($this->dbName === "") return array();
        return array("admin", "pages", "news", "faq");
    }

    /**
     * The links of the public files: name in the site root => target
     * @return array<string, string>
     */
    protected function frontLinks() : array
    {
        return array("sparkfront" => $this->sparkboxPath . "/sparkfront");
    }

    /**
     * The page class of the site from its title: "My Site" → MySitePage
     */
    protected function pageClassName() : string
    {
        $words = preg_split("/[^A-Za-z0-9]+/", iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $this->title) ?: "");
        $name = "";
        foreach ($words as $word) $name .= ucfirst(strtolower($word));
        $name = preg_replace("/^[0-9]+/", "", $name);
        if ($name === "") $name = "Site";
        return $name . "Page";
    }

    protected function settingsFile() : string
    {
        $lines = array(
            "<?php",
            "//the settings of the site - written by " . $this->layer() . "/tools/deploy.php, change them here",
            "include_once(\"config.php\");",
            "",
            "Spark::Set(Config::SITE_TITLE, " . var_export($this->title, true) . ");",
            "Spark::Set(Config::THEME, " . var_export($this->theme, true) . ");",
            "Spark::Set(Config::DB_ENABLED, " . (($this->dbName !== "") ? "TRUE" : "FALSE") . ");",
            "Spark::Set(Config::TRANSLATOR_ENABLED, FALSE);",
            "Spark::Set(Config::DEFAULT_LOCALE, " . var_export($this->locale, true) . ");",
            "Spark::Set(Config::TIMEZONE, " . var_export($this->timezone, true) . ");",
            "Spark::Set(Config::PHPMAILER_ENABLED, FALSE);",
        );
        return implode("\n", $lines) . "\n";
    }

    protected function dbconfigFile() : string
    {
        return implode("\n", array(
            "<?php",
            "//the database of the site - keep this file out of git",
            "\$prop = new DBConfig();",
            "\$prop->driverClass = \"PDO\";",
            "\$prop->database = " . var_export($this->dbName, true) . ";",
            "\$prop->user = " . var_export($this->dbName, true) . ";",
            "\$prop->pass = " . var_export($this->dbPass, true) . ";",
            "\$prop->host = " . var_export($this->dbHost, true) . ";",
            "\$prop->port = " . var_export($this->dbPort, true) . ";",
            "DBManager::Add(\$prop);",
        )) . "\n";
    }

    protected function routesFile() : string
    {
        $lines = array("<?php", "//the modules of the site by their address folder - the root index.php (Module::Route)");
        foreach ($this->routes() as $route) $lines[] = "Module::AddRoute(\"$route\");";
        if (count($this->routes()) < 1) $lines[] = "//Module::AddRoute(\"admin\"); - the admin and the other modules need the database";
        return implode("\n", $lines) . "\n";
    }

    /**
     * The frame of the site: the core site frame (SparkSitePage) with the site css
     */
    protected function pageClassFile(string $class) : string
    {
        $menu = "";
        if ($this->dbName === "") {
            //no database - the menu of the admin (Content > Menus) is not there: the home only
            $menu = "\n    protected function createMenu() : MenuItemList\n    {\n        \$menu = new MenuItemList();\n"
                . "        \$menu->append(new MenuItem(\"Home\", Spark::Get(Config::LOCAL) . \"/\"));\n        return \$menu;\n    }\n";
        }
        return "<?php\ninclude_once(\"pages/SparkSitePage.php\");\n" . (($menu !== "") ? "include_once(\"utils/menu/MenuItem.php\");\n" : "") . "\n"
            . "/**\n * The frame of the site - the core site frame (header, the main menu, the content, the footer) with the site css.\n"
            . " * The page class of the modules of the site (class/templates/modules/<module>/init.php).\n */\n"
            . "class $class extends SparkSitePage\n{\n"
            . "    public function __construct()\n    {\n        parent::__construct();\n"
            . "        \$this->head()->addCSS(Spark::Get(Config::LOCAL) . \"/css/site.css\");\n    }\n"
            . "\n    protected function headerLine() : string\n    {\n        return \"\";\n    }\n"
            . $menu . "}\n";
    }

    /**
     * The init.php of the modules of the site: the page class of the site - module => content
     * @return array<string, string>
     */
    protected function moduleInits(string $class) : array
    {
        $inits = array();
        foreach (array_merge(array("main"), array_diff($this->routes(), array("admin"))) as $module) {
            $inits[$module] = "<?php\n//$module module - the site frame\ninclude_once(\"class/pages/$class.php\");\n\n"
                . "Module::Active()->pageClass = $class::class;\n";
        }
        return $inits;
    }

    protected function siteCss() : string
    {
        return "/*\n * The css of the site - the site layer: it wins over the core and the store. The theme '$this->theme' gives the\n"
            . " * tokens (sparkfront/css/themes/$this->theme.css); set the ones this site changes here.\n */\n"
            . "@layer site {\n\n:root {\n    /* --theme-accent: #b9b3a3; */\n}\n\n}\n";
    }

    //the database ----------------------------------------------------------------------------------------------------

    /**
     * Connect with config/dbconfig.php and create the root administrator (--admin)
     */
    protected function database() : void
    {
        set_include_path($this->sparkboxPath . "/lib" . PATH_SEPARATOR . get_include_path());
        include_once("config.php");
        include_once("spark.php");
        include_once("debug.php");
        include_once("dbdriver/DBManager.php");
        include_once("dbdriver/PDODriver.php");
        include_once("auth/PasswordHash.php");
        include_once("beans/AdminUsersBean.php");

        Spark::Set(Config::PASSWORD_TYPE, PASSWORD_BCRYPT);
        Spark::SetObject(Config::PASSWORD_OPTIONS, array("cost" => 12));
        include($this->folder . "/config/dbconfig.php");

        try {
            DBManager::Driver();
        }
        catch (Exception $e) {
            $this->warn("no connection (" . $e->getMessage() . ") - create the database '$this->dbName' (user '$this->dbName') "
                . "in the hosting panel, then run this again in a new folder, or add the administrator in Settings > Admins");
            return;
        }
        $this->say("  connected");

        $this->createTables();

        if ($this->adminEmail === "") return;

        $admins = new AdminUsersBean();
        $count = $admins->query($admins->key());
        $count->stmt->limit(1);
        if ($count->count() > 0) {
            $this->warn("the database has administrators already - the root administrator is not added");
            return;
        }
        $hash = new PasswordHash($this->adminPass);
        $admins->insert(array(
            "userID" => 1,
            "email" => $this->adminEmail,
            "password" => $hash->getValue(),
            "context" => "",
            "access_level" => "Full Access",
            "fullname" => "Administrator",
        ));
        $this->say("  the root administrator $this->adminEmail (userID 1 - can not be deleted, suspended or limited)");
    }

    /**
     * The folders of the beans of the layers: folder => include prefix ("beans/") - the store adds its own
     * @return array<string, string>
     */
    protected function beanFolders() : array
    {
        return array($this->sparkboxPath . "/lib/beans" => "beans/");
    }

    /**
     * The tables of every bean of the layers - a bean creates its table when it is first used, but a query can join
     * a table whose bean was not used yet (the admin product list on a new store): all of them now
     */
    protected function createTables() : void
    {
        //the values a table creation reads (the languages: English and the language of the site)
        Spark::Set(Config::DEFAULT_LOCALE, $this->locale);

        //one file per bean class - a later layer replaces a bean of the same name (the store FAQItemsBean)
        $beans = array();
        foreach ($this->beanFolders() as $folder => $prefix) {
            foreach (glob($folder . "/*.php") as $file) $beans[basename($file, ".php")] = $prefix . basename($file);
        }

        //a view needs the tables it reads - the failed ones again while that creates more
        $created = 0;
        $failed = array();
        do {
            $retry = $failed;
            $failed = array();
            $before = $created;
            foreach ($beans as $class => $include) {
                try {
                    include_once($include);
                    if (!class_exists($class, false)) continue;
                    $reflection = new ReflectionClass($class);
                    if (!$reflection->isInstantiable() || !$reflection->isSubclassOf(DBTableBean::class)) continue;
                    $constructor = $reflection->getConstructor();
                    if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) continue;
                    new $class();
                    $created++;
                    unset($beans[$class]);
                }
                catch (Throwable $e) {
                    $failed[$class] = "$class (" . $e->getMessage() . ")";
                }
            }
        } while (count($failed) > 0 && $created > $before && $failed !== $retry);
        $this->say("  the tables of $created beans");
        foreach ($failed as $item) $this->warn("not created: $item");
    }

        //links -----------------------------------------------------------------------------------------------------------

    /**
     * The link '$link' to '$target': symlink(), else 'ln -s' as a process, else the command to run by hand
     */
    protected function link(string $target, string $link) : void
    {
        $name = basename($link);
        if (is_link($link) || file_exists($link)) {
            $this->say("  $name - present");
            return;
        }
        if (!file_exists($target)) {
            $this->warn("$name: the target '$target' does not exist");
            return;
        }
        if ($this->update && !$this->write) {
            $this->changes++;
            $this->say("  $name -> $target - to create");
            return;
        }
        $this->changes++;
        if (function_exists("symlink") && @symlink($target, $link)) {
            $this->say("  $name -> $target");
            return;
        }
        $command = "ln -s " . escapeshellarg($target) . " " . escapeshellarg($link);
        if (function_exists("exec")) {
            $output = array();
            $code = 1;
            @exec($command . " 2>&1", $output, $code);
            if ($code === 0 && is_link($link)) {
                $this->say("  $name -> $target (ln -s)");
                return;
            }
        }
        $this->warn("$name: links can not be created from PHP here - run:  $command");
    }

    //update ----------------------------------------------------------------------------------------------------------

    /**
     * The SparkBox part of an existing site root again
     * @return bool
     * @throws Exception
     */
    protected function updateSite() : bool
    {
        if (!is_dir($this->folder)) throw new Exception("'$this->folder' is not a folder");
        $this->folder = (string)realpath($this->folder);
        if ($this->appPath === "") $this->appPath = $this->folder;
        $root = $this->folder;

        $this->say("Site: $root" . ($this->write ? "" : "   (dry run - nothing is changed; --write to apply)"));
        $this->say("  " . $this->pathsLine());
        if (!is_file("$root/sparkboot.php")) $this->warn("no sparkboot.php in the site root - copy dist/sparkboot.php.dist");
        if (!is_file("$root/robots.txt")) $this->warn("no robots.txt in the site root - copy dist/robots.txt.dist (the sitemap line: the address of the site)");

        //a site with its own root script (not the main module) does not get the front controller
        $index = is_file("$root/index.php") ? (string)file_get_contents("$root/index.php") : "";
        $mainModule = ($index === "" || str_contains($index, "Module::Route()") || str_contains($index, 'Module::Run("main")'));
        if (!$mainModule && !$this->force) {
            $this->warn("index.php is not the main module (a root script of the site) - the front controller would send its unknown"
                . " addresses there. Nothing changed; --force when the site is on the main module.");
            return false;
        }

        $this->updateHtaccess();
        $this->updateIndex($index);
        $routes = $this->updateRoutes();
        $this->updateLinks($routes);

        $this->say("\n" . (($this->changes === 0) ? "Nothing to change." : ($this->write ? "$this->changes change(s) made." : "$this->changes change(s) to make - run again with --write.")));
        if ($this->warnings > 0) $this->say("$this->warnings warning(s) above.");
        if ($this->write && $this->changes > 0) $this->say("Copies of the changed files: <file>.before-deploy. OPcache may serve the old index.php for its revalidate time; purge the CDN cache after a change of addresses.");
        return true;
    }

    /**
     * The SparkBox block of .htaccess (dist/htaccess.dist) with the paths of this server
     */
    protected function htaccessBlock() : string
    {
        $template = (string)file_get_contents($this->sparkboxPath . "/dist/htaccess.dist");
        return trim(str_replace(array("_SPARKBOX_LIB_", "_APP_PATH_"), array($this->sparkboxPath . "/lib", $this->appPath), $template));
    }

    protected function updateHtaccess() : void
    {
        $this->say("\n1. .htaccess");
        $block = $this->htaccessBlock();

        //the lines of the template - the same lines in a hand-made file are SparkBox lines (moved into the block)
        $normalize = fn(string $line) : string => preg_replace("/\s+/", " ", trim($line));
        $ours = array();
        foreach (explode("\n", $block) as $line) {
            $line = $normalize($line);
            if ($line === "" || in_array($line, array("RewriteEngine On", "Options +FollowSymLinks"), true)) continue;
            if (str_starts_with($line, "<") || str_starts_with($line, "#")) continue;
            $ours[$line] = true;
        }
        $isOurs = function (string $line) use ($ours, $normalize) : bool {
            $line = $normalize($line);
            if (isset($ours[$line])) return true;
            //the paths of another checkout or the placeholders of the old template
            return (bool)preg_match("/^php_value (include_path|auto_prepend_file) /i", $line);
        };
        //the comments of the old templates (dist/htaccess.dist, modules/main/root.htaccess)
        $oldComment = function (string $line) : bool {
            $line = trim($line);
            if (!str_starts_with($line, "#")) return false;
            return (bool)preg_match("/SparkBox|sparkbox|main module|front controller|storage: images|versioned CSS|so browsers and the CDN|"
                . "Addresses that are not a file|Missing static files|code and configuration folders|PHP settings: web servers|"
                . "them from here|PHP-FPM \/ CGI servers|cp dist\/htaccess|rules of the site root|after the other rewrite/", $line);
        };

        $file = $this->folder . "/.htaccess";
        $current = is_file($file) ? (string)file_get_contents($file) : "";
        //the site lines: the file without its SparkBox block
        $site = preg_replace("/^# BEGIN SparkBox$.*?^# END SparkBox$\n?/ms", "", $current);

        $kept = array();
        $moved = array();
        foreach (explode("\n", $site) as $line) {
            if ($isOurs($line) || $oldComment($line)) {
                if (trim($line) !== "" && !str_starts_with(trim($line), "#")) $moved[] = trim($line);
                continue;
            }
            $kept[] = $line;
        }
        //IfModule sections left empty (or with only RewriteEngine / Options / comments) go too
        $text = implode("\n", $kept);
        do {
            $before = $text;
            $text = preg_replace("/^[ \t]*<IfModule [^>]+>\s*((RewriteEngine On|Options \+FollowSymLinks|#[^\n]*)\s*)*<\/IfModule>[ \t]*\n?/mi", "", $text);
        } while ($text !== $before);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text));

        $new = (($text !== "") ? $text . "\n\n" : "") . $block . "\n";
        if ($new === $current) {
            $this->say("  up to date");
            return;
        }
        if (count($moved) > 0) {
            $this->say("  SparkBox lines of the file moved into the block:");
            foreach ($moved as $line) $this->say("    - $line");
        }
        if ($text !== "") $this->say("  the site's own lines kept above the block (" . count(explode("\n", $text)) . " lines)");
        $this->say(($current === "") ? "  written new" : "  the block written");
        $this->put($file, $new);
        if (!$this->write) {
            $this->say("  ---- the new .htaccess ----");
            foreach (explode("\n", rtrim($new)) as $line) $this->say("  | $line");
            $this->say("  ---------------------------");
        }
    }

    protected function updateIndex(string $index) : void
    {
        $this->say("\n2. index.php");
        $controller = (string)file_get_contents($this->sparkboxPath . "/modules/main/index.php");
        if ($index === $controller) {
            $this->say("  up to date (Module::Route)");
            return;
        }
        $this->say("  " . (($index === "") ? "written" : "replaced") . " with the front controller (modules/main/index.php)");
        $this->put($this->folder . "/index.php", $controller);
    }

    /**
     * The module folders of the root: folder => module - links to a module entry folder (also when its target is
     * gone: .../modules/<name>) and folders with an index.php that runs a module
     * @return array<string, string>
     */
    protected function moduleFolders() : array
    {
        $root = $this->folder;
        $folders = array();
        foreach (scandir($root) as $entry) {
            if ($entry[0] === ".") continue;
            if (is_link("$root/$entry") && preg_match('#modules/([a-z0-9_]+)/?$#', (string)readlink("$root/$entry"), $m)) {
                $folders[$entry] = $m[1];
                continue;
            }
            if (!is_dir("$root/$entry")) continue;
            $entryIndex = "$root/$entry/index.php";
            if (!is_file($entryIndex)) continue;
            if (preg_match('/Module::Run\("([a-z0-9_]+)"\)/', (string)file_get_contents($entryIndex), $m)) $folders[$entry] = $m[1];
        }
        return $folders;
    }

    /**
     * config/routes.php - written from the module folders when the site has none; the routes: folder => module
     * @return array<string, string>
     */
    protected function updateRoutes() : array
    {
        $this->say("\n3. config/routes.php");
        $moduleFolders = $this->moduleFolders();
        $routesFile = $this->folder . "/config/routes.php";
        $routes = array();
        if (is_file($routesFile)) {
            if (preg_match_all('/Module::AddRoute\(\s*"([^"]+)"\s*(?:,\s*"([^"]+)"\s*)?\)/', (string)file_get_contents($routesFile), $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) $routes[$m[1]] = (($m[2] ?? "") !== "") ? $m[2] : $m[1];
            }
            $this->say("  routes: " . ((count($routes) > 0) ? implode(", ", array_map(fn($f, $n) => ($f === $n) ? $f : "$f -> $n", array_keys($routes), $routes)) : "none"));
        }
        else if (count($moduleFolders) > 0) {
            $lines = array("<?php", "//the modules of the site by their address folder - the root index.php (Module::Route)",
                           "//written by " . $this->layer() . "/tools/deploy.php from the module folders of the root");
            foreach ($moduleFolders as $folder => $name) {
                $lines[] = ($folder === $name) ? "Module::AddRoute(\"$folder\");" : "Module::AddRoute(\"$folder\", \"$name\");";
                $routes[$folder] = $name;
            }
            $this->say("  written from the module folders of the root: " . implode(", ", array_keys($moduleFolders)));
            $this->put($routesFile, implode("\n", $lines) . "\n");
        }
        else {
            $this->warn("no config/routes.php and no module folders - only the main module answers (dist/routes.php.dist)");
        }
        foreach ($moduleFolders as $folder => $name) {
            if (!isset($routes[$folder])) $this->warn("the module folder '$folder' ($name) has no route - it works only while the folder exists");
        }
        return $routes;
    }

    /**
     * The module links the routes answer (--remove-links), the links of the public files
     */
    protected function updateLinks(array $routes) : void
    {
        $this->say("\n4. links");
        $root = $this->folder;
        $moduleFolders = $this->moduleFolders();
        foreach ($moduleFolders as $folder => $name) {
            if (!isset($routes[$folder])) continue;
            $path = "$root/$folder";
            if (!is_link($path)) {
                $this->say("  $folder/ - a real folder (not a link): left; remove it by hand when the route works");
                continue;
            }
            if ($this->removeLinks) {
                $this->say("  $folder -> " . readlink($path) . " - removed (the route answers it)");
                $this->changes++;
                if ($this->write) unlink($path);
            }
            else {
                $this->say("  $folder -> " . readlink($path) . " - can go (--remove-links): the route answers it");
            }
        }
        foreach (scandir($root) as $entry) {
            if ($entry[0] !== "." && is_link("$root/$entry") && !file_exists("$root/$entry") && !isset($moduleFolders[$entry])) {
                $this->warn("'$entry' is a link to nothing (" . readlink("$root/$entry") . ")");
            }
        }
        foreach ($this->frontLinks() as $name => $target) $this->link($target, "$root/$name");
    }
}
