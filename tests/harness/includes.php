<?php
/**
 * Strict includes: every class a file uses (new, ::, extends, implements) is reachable through
 * its own include_once chain or the boot (sparkbox.php, sparkstore.php) - no file works only because another
 * file happened to load the class first. A class defined in more than one place (a site overrides a bean by its
 * name) is not checked, nor a use guarded by class_exists("Class", false) - instanceof and catch need no class
 * loaded. Used by tests/core/includes.test.php and the site suites.
 */

/**
 * [file => [included files], file => [defined classes], file => [used classes]]
 */
function includesScan(array $roots) : array
{
    $files = array();
    foreach ($roots as $root) {
        //a site root: its class/ folder only
        $walk = is_dir("$root/class") && !is_dir("$root/templates") ? "$root/class" : $root;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($walk, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $path = $file->getPathname();
            if (!str_ends_with($path, ".php") || str_contains($path, "/PHPMailer/") || str_contains($path, "/tests/")) continue;
            $files[$path] = true;
        }
    }

    $includes = array();
    $defines = array();
    $uses = array();
    foreach (array_keys($files) as $path) {
        $tokens = token_get_all(file_get_contents($path));
        $includes[$path] = array();
        $defines[$path] = array();
        $uses[$path] = array();
        $count = count($tokens);
        $next = function (int $i) use ($tokens, $count) : int {
            for ($i++; $i < $count; $i++) {
                if (is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) continue;
                return $i;
            }
            return $count;
        };
        $name = function (int $i) use ($tokens, $count) : string {
            return ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) ? $tokens[$i][1] : "";
        };
        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i])) continue;
            [$id] = $tokens[$i];
            if ($id === T_INCLUDE_ONCE || $id === T_REQUIRE_ONCE) {
                $j = $next($i);
                if ($j < $count && $tokens[$j] === "(") $j = $next($j);
                if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                    $file = trim($tokens[$j][1], "\"'");
                    foreach ($roots as $root) {
                        if (is_file("$root/$file")) {
                            $includes[$path][] = realpath("$root/$file");
                            break;
                        }
                    }
                }
            }
            else if (in_array($id, array(T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM), true)) {
                $prev = $i - 1;
                while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) $prev--;
                if ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_DOUBLE_COLON) continue;
                $class = $name($next($i));
                if ($class !== "") $defines[$path][] = $class;
            }
            else if ($id === T_NEW || $id === T_EXTENDS) {
                $class = $name($next($i));
                if ($class !== "") $uses[$path][] = $class;
            }
            else if ($id === T_IMPLEMENTS) {
                for ($j = $next($i); $j < $count && $tokens[$j] !== "{"; $j = $next($j)) {
                    if ($name($j) !== "") $uses[$path][] = $name($j);
                }
            }
            else if ($id === T_STRING && $next($i) < $count && is_array($tokens[$next($i)]) && $tokens[$next($i)][0] === T_DOUBLE_COLON) {
                $uses[$path][] = $tokens[$i][1];
            }
        }
    }
    return array($includes, $defines, $uses);
}

function includesReach(string $file, array $includes) : array
{
    $seen = array();
    $stack = array($file);
    while ($stack) {
        $current = array_pop($stack);
        if (isset($seen[$current])) continue;
        $seen[$current] = true;
        foreach ($includes[$current] ?? array() as $included) $stack[] = $included;
    }
    return $seen;
}

/**
 * The files of '$check' (a folder) that use a class without its include_once - "file: Class" lines.
 * $roots: the include roots in layer order (core lib, store lib, site root); $check is scanned with them.
 * @param array $roots
 * @param string $check
 * @return array
 */
function includesMissing(array $roots, string $check) : array
{
    $core = $roots[0];
    $scan = array($core);
    foreach (array_slice($roots, 1) as $root) $scan[] = $root;
    [$includes, $defines, $uses] = includesScan($scan);

    $where = array();
    foreach ($defines as $file => $classes) {
        foreach ($classes as $class) $where[$class][] = $file;
    }

    //uses guarded by class_exists("Class", false) - the file does not load the class on purpose
    $guarded = array();
    foreach (array_keys($uses) as $file) {
        if (!str_starts_with($file, $check)) continue;
        if (preg_match_all('#class_exists\(\s*["\']([A-Za-z_]\w*)["\']\s*,\s*false#i', (string)file_get_contents($file), $m)) {
            //the guarded class and what its file loads with it
            foreach ($m[1] as $class) {
                $guarded[$file][$class] = true;
                if (!isset($where[$class]) || count($where[$class]) > 1) continue;
                foreach (array_keys(includesReach($where[$class][0], $includes)) as $loaded) {
                    foreach ($defines[$loaded] ?? array() as $with) $guarded[$file][$with] = true;
                }
            }
        }
    }

    $boot = array();
    foreach ($roots as $root) {
        foreach (array("$root/sparkbox.php", "$root/sparkstore.php") as $file) {
            if (isset($includes[$file])) $boot += includesReach($file, $includes);
        }
    }

    $missing = array();
    foreach ($uses as $file => $classes) {
        if (!str_starts_with($file, $check)) continue;
        $reach = includesReach($file, $includes) + $boot;
        foreach (array_unique($classes) as $class) {
            if (!isset($where[$class]) || count($where[$class]) > 1) continue;
            if (in_array($class, $defines[$file], true)) continue;
            if (isset($guarded[$file][$class])) continue;
            if (!isset($reach[$where[$class][0]])) $missing[] = "$file: $class";
        }
    }
    return $missing;
}
