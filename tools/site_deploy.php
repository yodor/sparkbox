<?php
/**
 * Site deploy - puts the SparkBox part of a site root in place. Dry run by default: it prints what it would
 * change and changes nothing. Run it on the server, from the sparkbox checkout the site uses.
 *
 * Usage: php tools/site_deploy.php <site root> [--write] [--remove-links] [--force]
 *                                  [--sparkbox=<path>] [--app=<path>]
 *   <site root>       the document root of the site (sparkboot.php, config/, class/)
 *   --write           make the changes (each changed file keeps a copy: <file>.before-deploy)
 *   --remove-links    remove the module links of the root that config/routes.php now answers
 *   --force           also for a site whose index.php is not the main module (a root script of its own -
 *                     without --force such a site is left as it is)
 *   --sparkbox=<path> the sparkbox checkout as the web server sees it (default: this checkout)
 *   --app=<path>      the site root as the web server sees it (default: <site root>)
 *
 * What it does:
 *   1. .htaccess - the block between '# BEGIN SparkBox' and '# END SparkBox' from dist/htaccess.dist (the paths
 *      filled in), at the end of the file. The lines outside the block are the site's own and stay; the SparkBox
 *      lines of a hand-made file (php_value include_path / auto_prepend_file, the storage, versioned, class/config
 *      and front controller rules) are moved into the block.
 *   2. index.php - the front controller (modules/main/index.php).
 *   3. config/routes.php - written from the module links of the root when the site has none.
 *   4. The module links (--remove-links) and the public file links (sparkfront, storefront: checked only - the
 *      commands to create them are printed).
 */

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

$args = array_slice($argv, 1);
$root = "";
$opt = array("write" => false, "remove-links" => false, "force" => false, "sparkbox" => "", "app" => "");
foreach ($args as $arg) {
    if (preg_match("/^--(write|remove-links|force)$/", $arg, $m)) $opt[$m[1]] = true;
    else if (preg_match("/^--(sparkbox|app)=(.+)$/", $arg, $m)) $opt[$m[1]] = rtrim($m[2], "/");
    else if ($root === "") $root = rtrim($arg, "/");
}
if ($root === "" || !is_dir($root)) {
    fwrite(STDERR, "Usage: php tools/site_deploy.php <site root> [--write] [--remove-links] [--force] [--sparkbox=<path>] [--app=<path>]\n");
    exit(1);
}
$root = realpath($root);
$sparkbox = ($opt["sparkbox"] !== "") ? $opt["sparkbox"] : dirname(__DIR__);
$app = ($opt["app"] !== "") ? $opt["app"] : $root;
$write = $opt["write"];

$changes = 0;
$warnings = 0;

function say(string $text) : void
{
    echo $text . "\n";
}

function warn(string $text) : void
{
    global $warnings;
    $warnings++;
    echo "  ! " . $text . "\n";
}

/**
 * Write '$content' to '$file' (a copy of the old file first) - only with --write
 */
function put(string $file, string $content) : void
{
    global $write, $changes;
    $changes++;
    if (!$write) return;
    if (is_file($file)) copy($file, $file . ".before-deploy");
    file_put_contents($file, $content);
}

say("Site: $root" . ($write ? "" : "   (dry run - nothing is changed; --write to apply)"));
say("  sparkbox: $sparkbox    app path: $app");
if (!is_file("$root/sparkboot.php")) warn("no sparkboot.php in the site root - copy dist/sparkboot.php.dist");
if (!is_dir("$sparkbox/lib")) warn("no lib/ in '$sparkbox' - give the sparkbox path with --sparkbox=");

//a site with its own root script (not the main module) does not get the front controller - its unknown addresses
//would go to that script
$index = is_file("$root/index.php") ? (string)file_get_contents("$root/index.php") : "";
$mainModule = ($index === "" || str_contains($index, "Module::Route()") || str_contains($index, 'Module::Run("main")'));
if (!$mainModule && !$opt["force"]) {
    warn("index.php is not the main module (a root script of the site) - the front controller would send its unknown"
        . " addresses there. Nothing changed; --force when the site is on the main module.");
    exit(1);
}

//1. .htaccess ------------------------------------------------------------------------------------------------------
say("\n1. .htaccess");
$template = (string)file_get_contents(__DIR__ . "/../dist/htaccess.dist");
$block = trim(str_replace(array("_SPARKBOX_LIB_", "_APP_PATH_"), array("$sparkbox/lib", $app), $template));

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

$file = "$root/.htaccess";
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
    say("  up to date");
}
else {
    if (count($moved) > 0) {
        say("  SparkBox lines of the file moved into the block:");
        foreach ($moved as $line) say("    - $line");
    }
    if ($text !== "") say("  the site's own lines kept above the block (" . count(explode("\n", $text)) . " lines)");
    say(($current === "") ? "  written new" : "  the block written");
    put($file, $new);
    if (!$write) {
        say("  ---- the new .htaccess ----");
        foreach (explode("\n", rtrim($new)) as $line) say("  | $line");
        say("  ---------------------------");
    }
}

//2. index.php - the front controller -------------------------------------------------------------------------------
say("\n2. index.php");
$controller = (string)file_get_contents(__DIR__ . "/../modules/main/index.php");
if ($index === $controller) {
    say("  up to date (Module::Route)");
}
else {
    say("  " . (($index === "") ? "written" : "replaced") . " with the front controller (modules/main/index.php)");
    put("$root/index.php", $controller);
}

//3. config/routes.php ----------------------------------------------------------------------------------------------
say("\n3. config/routes.php");
//the module folders of the root: links (or folders) with an index.php that runs a module
$moduleLinks = array();
foreach (scandir($root) as $entry) {
    if ($entry[0] === ".") continue;
    //a link to a module entry folder - also when its target is gone (.../modules/<name>)
    if (is_link("$root/$entry") && preg_match('#modules/([a-z0-9_]+)/?$#', (string)readlink("$root/$entry"), $m)) {
        $moduleLinks[$entry] = $m[1];
        continue;
    }
    if (!is_dir("$root/$entry")) continue;
    $entryIndex = "$root/$entry/index.php";
    if (!is_file($entryIndex)) continue;
    if (preg_match('/Module::Run\("([a-z0-9_]+)"\)/', (string)file_get_contents($entryIndex), $m)) $moduleLinks[$entry] = $m[1];
}
$routesFile = "$root/config/routes.php";
$routes = array();
if (is_file($routesFile)) {
    if (preg_match_all('/Module::AddRoute\(\s*"([^"]+)"\s*(?:,\s*"([^"]+)"\s*)?\)/', (string)file_get_contents($routesFile), $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) $routes[$m[1]] = ($m[2] ?? "") !== "" ? $m[2] : $m[1];
    }
    say("  routes: " . ((count($routes) > 0) ? implode(", ", array_map(fn($f, $n) => ($f === $n) ? $f : "$f -> $n", array_keys($routes), $routes)) : "none"));
}
else if (count($moduleLinks) > 0) {
    $lines = array("<?php", "//the modules of the site by their address folder - the root index.php (Module::Route)",
                   "//written by tools/site_deploy.php from the module links of the root");
    foreach ($moduleLinks as $folder => $name) {
        $lines[] = ($folder === $name) ? "Module::AddRoute(\"$folder\");" : "Module::AddRoute(\"$folder\", \"$name\");";
        $routes[$folder] = $name;
    }
    say("  written from the module links of the root: " . implode(", ", array_keys($moduleLinks)));
    put($routesFile, implode("\n", $lines) . "\n");
}
else {
    warn("no config/routes.php and no module links - only the main module answers (dist/routes.php.dist)");
}
foreach ($moduleLinks as $folder => $name) {
    if (!isset($routes[$folder])) warn("the module folder '$folder' ($name) has no route - it works only while the folder exists");
}

//4. links ----------------------------------------------------------------------------------------------------------
say("\n4. links");
foreach ($moduleLinks as $folder => $name) {
    if (!isset($routes[$folder])) continue;
    $path = "$root/$folder";
    if (!is_link($path) && !is_dir($path)) continue;
    if (!is_link($path)) {
        say("  $folder/ - a real folder (not a link): left; remove it by hand when the route works");
        continue;
    }
    if ($opt["remove-links"]) {
        say("  $folder -> " . readlink($path) . " - removed (the route answers it)");
        $changes++;
        if ($write) unlink($path);
    }
    else {
        say("  $folder -> " . readlink($path) . " - can go (--remove-links): the route answers it");
    }
}
//a dangling link (its target gone)
foreach (scandir($root) as $entry) {
    if ($entry[0] !== "." && is_link("$root/$entry") && !file_exists("$root/$entry") && !isset($moduleLinks[$entry])) warn("'$entry' is a link to nothing (" . readlink("$root/$entry") . ")");
}
$store = false;
foreach ($routes as $name) {
    if (in_array($name, array("products", "checkout", "account"), true)) $store = true;
}
$fronts = array("sparkfront" => "$sparkbox/sparkfront");
if ($store) $fronts["storefront"] = dirname($sparkbox) . "/sparkstore/storefront";
foreach ($fronts as $name => $target) {
    if (file_exists("$root/$name")) say("  $name - present");
    else warn("$name missing - the pages are unstyled without it:  ln -s $target $root/$name");
}

say("\n" . (($changes === 0) ? "Nothing to change." : ($write ? "$changes change(s) made." : "$changes change(s) to make - run again with --write.")));
if ($warnings > 0) say("$warnings warning(s) above.");
if ($write && $changes > 0) say("Copies of the changed files: <file>.before-deploy. OPcache may serve the old index.php for its revalidate time.");
