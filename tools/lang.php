<?php
/**
 * The texts of the code and the language packs (<layer>/lang/<code>.php):
 *
 *   php sparkbox/tools/lang.php <code> [--store=<sparkstore lib>] [--site=<site root>] [--unused]
 *
 * Lists the texts of the code (core, the store, the store features - lib/store/features/<feature> with its own pack,
 * the site class/ - in that order) that the merged packs of '<code>'
 * do not translate, with the layer whose pack should have them (the first layer that uses the text). With --unused:
 * also the pack entries no code of the layers uses. A text is found where the code writes it as a literal:
 * tr("..."), new MenuItem("..."), the label of DataInputFactory::Create(...), setTitle / setContentTitle("...") - and
 * tr("...") of the scripts (sparkfront/js, storefront/js: their texts come from HTMLHead::addTexts).
 * Texts built at run time (data, column names) are not seen - their entries are not reported unused if they are
 * there on purpose; check before removing one.
 */

$args = array_slice($argv, 1);
$code = "";
$options = array("store" => "", "site" => "", "unused" => false);
foreach ($args as $arg) {
    if (preg_match("/^--(store|site)=(.+)$/", $arg, $m)) $options[$m[1]] = rtrim($m[2], "/");
    else if ($arg === "--unused") $options["unused"] = true;
    else if (preg_match("/^[a-z]{2}$/", $arg)) $code = $arg;
    else {
        fwrite(STDERR, "Unknown argument: $arg\n");
        exit(2);
    }
}
if ($code === "") {
    fwrite(STDERR, "Usage: php tools/lang.php <two-letter code> [--store=<sparkstore lib>] [--site=<site root>] [--unused]\n");
    exit(2);
}

//the layers: name => array(code folders, pack file)
$core = realpath(__DIR__ . "/../lib");
$layers = array("core" => array(array($core, dirname($core) . "/sparkfront/js"), "$core/lang/$code.php"));
if ($options["store"] !== "") {
    $layers["store"] = array(array($options["store"] . "/store", dirname($options["store"]) . "/storefront/js"), $options["store"] . "/store/lang/$code.php");
    //the features: a layer each (their texts in their own pack, loaded while the feature is enabled)
    foreach (glob($options["store"] . "/store/features/*", GLOB_ONLYDIR) ?: array() as $feature) {
        $layers["feature " . basename($feature)] = array(array($feature), "$feature/lang/$code.php");
    }
}
if ($options["site"] !== "") $layers["site"] = array(array($options["site"] . "/class"), $options["site"] . "/class/lang/$code.php");

/**
 * The texts the PHP and JS files of '$folder' write as literals of the known calls
 * @return array<string, string> text => the first file
 */
function codeTexts(string $folder) : array
{
    $texts = array();
    $patterns = array(
        "/\\btr\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/s",
        "/new\\s+MenuItem\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/s",
        "/DataInputFactory::Create\\([^,]+,[^,]+,\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/s",
        "/->set(?:Content)?Title\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/s",
    );
    if (!is_dir($folder)) return $texts;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        if (str_contains($path, "/PHPMailer/") || str_contains($path, "/lang/") || str_contains($path, "/tiny_mce/")) continue;
        //the features are layers of their own
        if (str_contains($path, "/store/features/") && !str_contains($folder, "/store/features/")) continue;
        if (preg_match("/\\.js$/", $path) && !preg_match("/\\.min\\.js$/", $path)) {
            //a script: its tr("...") - the code without its comments
            $source = (string)preg_replace("#/\\*.*?\\*/|(?<![:\"'])//[^\\n]*#s", "", (string)file_get_contents($path));
            if (preg_match_all($patterns[0], $source, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $text = stripcslashes($match[2]);
                    if ($text !== "" && !isset($texts[$text])) $texts[$text] = $path;
                }
            }
            continue;
        }
        if (!preg_match("/\\.php$/", $path)) continue;
        //the code without its comments (an example in a comment is not a text of the code)
        $source = "";
        foreach (token_get_all((string)file_get_contents($path)) as $token) {
            if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) continue;
            $source .= is_array($token) ? $token[1] : $token;
        }
        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $source, $m, PREG_SET_ORDER)) continue;
            foreach ($m as $match) {
                $text = stripcslashes($match[2]);
                //a text with PHP variables in it is built at run time
                if ($text === "" || ($match[1] === '"' && str_contains($text, '$'))) continue;
                if (!isset($texts[$text])) $texts[$text] = $path;
            }
        }
    }
    return $texts;
}

$used = array();
$packs = array();
foreach ($layers as $name => [$folders, $pack]) {
    foreach ($folders as $folder) {
        foreach (codeTexts($folder) as $text => $file) {
            if (!isset($used[$text])) $used[$text] = array($name, $file);
        }
    }
    $packs[$name] = is_file($pack) ? (array)(include $pack) : array();
}
$merged = array();
foreach ($packs as $pack) $merged = array_replace($merged, $pack);

$missing = array();
foreach ($used as $text => [$layer, $file]) {
    if (isset($merged[$text])) continue;
    //a text already in the language of the pack (not English) is not a key to translate
    if ($code !== "en" && preg_match("/[\\x{0400}-\\x{04FF}\\x{0370}-\\x{03FF}]/u", $text)) continue;
    $missing[$layer][] = array($text, $file);
}

$total = 0;
foreach ($missing as $layer => $items) {
    echo "Missing in the $layer pack ($code): " . count($items) . "\n";
    foreach ($items as [$text, $file]) echo "  " . var_export($text, true) . "    (" . $file . ")\n";
    $total += count($items);
}
if ($total === 0) echo "Every text of the code has a translation ($code)\n";

if ($options["unused"]) {
    foreach ($packs as $layer => $pack) {
        $unused = array_diff(array_keys($pack), array_keys($used));
        if (count($unused) === 0) continue;
        echo "Not used by the code - $layer pack ($code): " . count($unused) . "\n";
        foreach ($unused as $text) echo "  " . var_export($text, true) . "\n";
    }
}
exit(($total > 0) ? 1 : 0);
