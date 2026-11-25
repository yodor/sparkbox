<?php
include_once("utils/url/FileVersion.php");

/**
 * The stylesheets or the scripts of a page in one response (HTMLHead with bundles enabled): fewer requests before the
 * page can render. The address names its files, so any server answers it - nothing is stored:
 *
 *   <LOCAL>/assets/bundle/<hash>.css?f=<files>     (the storage route of sparkbox - sparkstorage.php)
 *
 *   files   the URL paths of the files below the document root ('/sparkfront/css/Action.css', a stylesheet with a
 *           media query: '<path>@<media>'), joined by '|', deflated and base64url encoded
 *   hash    of the files and their versions (FileVersion) - a changed file is a new address, so the answer is
 *           cached for a year (an address of an older version is answered with the current files for a minute)
 *
 * Stylesheets: their relative url() addresses are made absolute (the bundle is served from another folder); a
 * stylesheet with a media query is wrapped in @media. Scripts: joined in their order.
 */
class AssetBundle
{
    const string TYPE_CSS = "css";
    const string TYPE_JS = "js";

    const string ROUTE = "bundle/";
    const string KEY_FILES = "f";

    //the files of one bundle at most - an address can not make the server read more
    const int MAX_FILES = 100;

    /**
     * The address of the bundle of '$files' (URL paths, a stylesheet may have '@<media>') of '$type'
     * @param array<string> $files
     */
    public static function Href(string $type, array $files) : string
    {
        $list = implode("|", $files);
        $files = rtrim(strtr(base64_encode((string)gzdeflate($list, 9)), "+/", "-_"), "=");
        return Spark::Get(Config::LOCAL) . "/assets/" . AssetBundle::ROUTE . AssetBundle::Hash($list) . "." . $type
            . "?" . AssetBundle::KEY_FILES . "=" . $files;
    }

    /**
     * The hash of the files of '$list' and their versions
     */
    protected static function Hash(string $list) : string
    {
        $versions = array();
        foreach (explode("|", $list) as $entry) {
            $path = explode("@", $entry, 2)[0];
            $versions[] = (new FileVersion($path))->token();
        }
        return substr(sha1($list . "#" . implode(",", $versions)), 0, 16);
    }

    /**
     * The files of an address: the valid entries ('<path>' or '<path>@<media>') - a file of another type, a path
     * outside the document root or a missing file is an error
     * @return array<string>
     * @throws Exception
     */
    protected static function Files(string $type, string $encoded) : array
    {
        $data = base64_decode(strtr($encoded, "-_", "+/"), true);
        $list = ($data === false) ? false : @gzinflate($data, 65536);
        if (!is_string($list) || $list === "") throw new Exception("Bad bundle");

        $entries = explode("|", $list);
        if (count($entries) > AssetBundle::MAX_FILES) throw new Exception("Bad bundle");
        $root = (string)($_SERVER["DOCUMENT_ROOT"] ?? "");
        foreach ($entries as $entry) {
            [$path, $media] = array_pad(explode("@", $entry, 2), 2, "");
            if (!preg_match("#^(/[A-Za-z0-9_.-]+)+\." . $type . "$#", $path) || str_contains($path, "..")) throw new Exception("Bad bundle");
            if ($media !== "" && ($type !== AssetBundle::TYPE_CSS || !preg_match("#^[A-Za-z0-9 ():,.-]+$#", $media))) throw new Exception("Bad bundle");
            if (!is_file($root . $path)) throw new Exception("Bad bundle");
        }
        return $entries;
    }

    /**
     * The contents of the bundle of '$entries'
     * @param array<string> $entries
     */
    public static function Contents(string $type, array $entries) : string
    {
        $root = (string)($_SERVER["DOCUMENT_ROOT"] ?? "");
        $parts = array();
        foreach ($entries as $entry) {
            [$path, $media] = array_pad(explode("@", $entry, 2), 2, "");
            $contents = (string)file_get_contents($root . $path);
            if ($type === AssetBundle::TYPE_JS) {
                $parts[] = "/* $path */\n" . $contents . "\n;";
                continue;
            }
            $contents = AssetBundle::AbsoluteURLs($contents, dirname($path));
            if ($media !== "") $contents = "@media $media {\n" . $contents . "\n}";
            $parts[] = "/* $path */\n" . $contents;
        }
        return implode("\n", $parts) . "\n";
    }

    /**
     * The relative url() addresses of a stylesheet of the folder '$folder' (URL path) made absolute
     */
    public static function AbsoluteURLs(string $css, string $folder) : string
    {
        return (string)preg_replace_callback("#url\(\s*(['\"]?)([^'\")]+)\\1\s*\)#i", function (array $m) use ($folder) : string {
            $url = trim($m[2]);
            if (preg_match("#^(data:|[a-z][a-z0-9+.-]*:|/|\#)#i", $url)) return $m[0];
            $parts = array();
            foreach (explode("/", rtrim($folder, "/") . "/" . $url) as $part) {
                if ($part === "" || $part === ".") continue;
                if ($part === "..") { array_pop($parts); continue; }
                $parts[] = $part;
            }
            return "url(" . $m[1] . "/" . implode("/", $parts) . $m[1] . ")";
        }, $css);
    }

    /**
     * Answer the bundle of the storage route '$route' ('bundle/<hash>.<type>') - sparkstorage.php
     */
    public static function Respond(string $route) : void
    {
        header_remove();
        try {
            if (!preg_match("#^" . AssetBundle::ROUTE . "([0-9a-f]{16})\.(css|js)$#", $route, $m)) throw new Exception("Bad bundle");
            [, $hash, $type] = $m;
            $entries = AssetBundle::Files($type, (string)($_GET[AssetBundle::KEY_FILES] ?? ""));
            $contents = AssetBundle::Contents($type, $entries);
        }
        catch (Exception $e) {
            http_response_code(404);
            header("Cache-Control: no-store");
            exit;
        }
        header("Content-Type: " . (($type === AssetBundle::TYPE_CSS) ? "text/css" : "text/javascript") . "; charset=utf-8");
        //the address of the current versions is for good, an older one (a page cached before a change) for a minute
        $current = hash_equals(AssetBundle::Hash(implode("|", $entries)), $hash);
        header("Cache-Control: " . ($current ? "public, max-age=31536000, immutable" : "public, max-age=60"));
        header("X-Content-Type-Options: nosniff");
        echo $contents;
        exit;
    }
}
