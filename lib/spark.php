<?php
final class SparkLoader
{

    /**
     * Locations to search during include:
     * 'key' => Hold the absolute path to search
     * 'value' => prefix to append to the path
     * @var array
     */
    protected static array $locations = array();

    /**
     * Current search prefix ex: 'beans', 'auth', 'forms'
     * @var string
     */
    protected string $prefix = "";


    /**
     *  Default search locations are initialized from SparkBox::IncludePath using
     *  $location = "/sparkbox_path/lib", prefix = "";
     *  $location = "/spark_module_path/lib", prefix = "module";
     *  $location = "/app_path", prefix = "class";
     *
     * @param string $searchPrefix
     * @return SparkLoader
     */
    public static function Factory(string $searchPrefix = ""): SparkLoader
    {
        return new SparkLoader($searchPrefix);
    }


    /**
     * Add $location to the loader search locations using prefix $prefix
     *
     * @param string $location
     * @param string $prefix
     * @return void
     */
    public static function AddLocation(string $location, string $prefix): void
    {
        SparkLoader::$locations[realpath($location)] = $prefix;
        //Debug::ErrorLog("Loader locations: " , SparkLoader::$locations);
    }

    /**
     * Setup Class loader to search all enabled locations and using current search prefix - $prefix
     *
     * @param string $prefix
     */
    private function __construct(string $prefix="")
    {
        $this->prefix = $prefix;
        //Debug::ErrorLog("Using prefix [$this->prefix]: ", SparkLoader::$locations);
    }

    /**
     * Search all SparkLoader::$locations and try to include $fileName from there using the current search prefix
     * Stop searching on first match if $includeAll is false
     * @param string $fileName
     * @param bool $includeAll Default True - include all files named $fileName searching all locations
     * @return void
     */
    public function include(string $fileName, bool $includeAll) : void
    {

        Debug::ErrorLog("Searching: $fileName.php | Include all: ".($includeAll ? "Yes" : "No"));

        $found = 0;
        foreach (SparkLoader::$locations as $includePath => $includePrefix) {

            //Debug::ErrorLog("Searching: $fileName.php in $includePath/$includePrefix/{$this->prefix}");

            $includeFile = Spark::PathParts($includePath, $includePrefix, $this->prefix, $fileName . ".php");

            if (file_exists($includeFile)) {
                $found++;
                Debug::ErrorLog("Including: ".$includeFile);
                include_once($includeFile);

                if (!$includeAll) break;
            }
            else {
                //Debug::ErrorLog("File not found: ".$includeFile);
            }
        }
        Debug::ErrorLog("Included [$found] files");

    }

    /**
     * Load class definition $className
     *
     * @param string $className
     * @return void
     * @throws Exception Throws if class_exists($className) returns false
     */
    public function define(string $className) : void
    {
        if (class_exists($className, FALSE)) {
            Debug::ErrorLog("Class already defined: ".$className);
            return;
        }

        //stop on first match
        $this->include($className, false);

        if (!class_exists($className, FALSE)) {
            Debug::ErrorLog("Class load failed: $className");
            throw new Exception("Class load failed: " . $className);
        }
        Debug::ErrorLog("Class definition loaded: ".$className);
    }

    /**
     * Load class definition for $className and create a new instance checking type is $classType
     *
     * @param string $className
     * @param string $classType
     * @return mixed
     * @throws Exception
     */
    public function instance(string $className, string $classType) : object
    {
        $this->define($className);
        $object = new $className();
        Debug::ErrorLog("Created object[".get_class($object)."] - Requested instance of $classType");
        if (!($object instanceof $classType)) throw new Exception("Object is not instance of $classType");
        return $object;
    }
}
final class Marshall {

    private function __construct() {}

    final static public function String(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_null($value)) {
            return '';
        }

        // Let PHP perform standard string conversion for scalars & objects with __toString()
        return (string) $value;
    }

    final static public function Boolean(mixed $value): bool
    {
        if (is_string($value)) {
            $value = trim(strtolower($value));
            if (in_array($value, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }
    final static public function Float(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = str_replace(',', '', trim($value));
            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }

        throw new Exception("Invalid float: " . $value);
    }

    final static public function Integer(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $clean = str_replace(',', '', trim($value));
            if (is_numeric($clean)) {
                return (int) $clean;
            }
        }

        throw new Exception("Invalid integer: " . $value);
    }

    /**
     * Return integer value from string of format 'number KB, MB, GB, TB, PB'
     * Ex 1M = 1 * 1024^2
     * @param string $umf
     * @return int
     */
    final static public function FromByteLabel(string $umf) : int
    {
        $result = 0;
        $sizes = array("K", "M", "G", "T", "P", "E", "Z", "Y");
        $umf = trim($umf);
        foreach ($sizes as $idx => $size) {
            if (str_ends_with($umf, $size) || str_ends_with($umf, $size."B") || str_ends_with($umf, $size."Bytes")) {
                $result = intval($umf) * pow(1024, ($idx+1));
                break;
            }
        }
        return $result;
    }

}

final class Spark {

    static array $defines = array();
    static array $constDefines = array();

    final private function __construct() {}

    final static function PathParts(string ... $parts) : string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Add $path to the current include_path and SparkLoader locations enabling loader prefix $loaderPrefix
     * @param string $path
     * @param string $loaderPrefix
     * @return void
     */
    final static function IncludePath(string $path, string $loaderPrefix) : void
    {
        $includes = array_flip(explode(PATH_SEPARATOR, get_include_path()));
        $includes[realpath($path)] = true;
        unset($includes["."]);
        $includes["."] = true;
        set_include_path(implode(PATH_SEPARATOR, array_keys($includes)));

        SparkLoader::AddLocation($path, $loaderPrefix);

        //error_log("Include Path: " . get_include_path());
    }

    final static public function Initialize() : void
    {

        Spark::Set(Config::APP_PATH, constant("APP_PATH"), true);

        $doc_full = realpath($_SERVER['DOCUMENT_ROOT']);
        $location = preg_replace("!^{$doc_full}!", "", constant("APP_PATH"));

        Spark::Set(Config::LOCAL, $location, true);
        Spark::Set(Config::SPARK_LOCAL, $location . "/sparkfront", true);

        Spark::Set(Config::ADMIN_LOCAL, $location . "/admin", true);
        Spark::Set(Config::STORAGE_URL, $location . "/sparkboot.php?StorageRequest", true);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        Spark::Set(Config::SITE_PROTOCOL, $protocol, true);

        $site_domain = $_SERVER["HTTP_HOST"];
        Spark::Set(Config::SITE_DOMAIN, $site_domain, true);

        Spark::Set(Config::SITE_URL, $protocol . $site_domain . $location, true);

        Spark::Set(Config::COOKIE_DOMAIN, ".".$site_domain, true); // or .domain.com

        //$umf = Marshall::FromByteLabel(ini_get("upload_max_filesize"));
        Spark::Set(Config::UPLOAD_MAX_SIZE, Marshall::FromByteLabel(ini_get("post_max_size")), true);
        Spark::Set(Config::MEMORY_LIMIT, Marshall::FromByteLabel(ini_get("memory_limit")), true);
        Spark::Set(Config::UPLOAD_MAX_FILESIZE, Marshall::FromByteLabel(ini_get("upload_max_filesize")), true);
        //end static values

        //
        Spark::Set(Config::TITLE_PATH_SEPARATOR, " :: ");

        Spark::Set(Config::IMAGE_UPLOAD_DEFAULT_WIDTH, 1280);
        Spark::Set(Config::IMAGE_UPLOAD_DEFAULT_HEIGHT, 720);
        Spark::Set(Config::IMAGE_UPLOAD_DOWNSCALE, TRUE);
        Spark::Set(Config::IMAGE_UPLOAD_UPSCALE, FALSE);
        Spark::Set(Config::IMAGE_UPLOAD_STORE_QUALITY, 80);

        include_once("storage/WatermarkPosition.php");
        include_once("utils/ImageType.php");
        Spark::Set(Config::IMAGE_SCALER_WATERMARK_ENABLED, false);
        Spark::Set(Config::IMAGE_SCALER_WATERMARK_FILENAME, "");
        Spark::Set(Config::IMAGE_SCALER_WATERMARK_POSITION, WatermarkPosition::BOTTOM_RIGHT->value);

        //force output to webp
        Spark::Set(Config::IMAGE_SCALER_OUTPUT_FORMAT, ImageType::TYPE_WEBP->value);
        Spark::Set(Config::IMAGE_SCALER_OUTPUT_QUALITY, 80);

        /**
         * Sender Name for emails sent from this site using the Mailer class
         * Default: SITE_DOMAIN
         */
        Spark::Set(Config::DEFAULT_SERVICE_NAME, $site_domain);
        /**
         * Sender email for emails sent from this site using the Mailer class
         * Default info@SITE_DOMAIN
         */
        Spark::Set(Config::DEFAULT_SERVICE_EMAIL, "info@" . $site_domain);

        Spark::Set(Config::TRANSLATOR_ENABLED, FALSE);
        Spark::Set(Config::DB_ENABLED, FALSE);

        Spark::Set(Config::DEFAULT_LANGUAGE, "english");
        Spark::Set(Config::DEFAULT_LANGUAGE_ISO3, "eng");
        Spark::Set(Config::DEFAULT_LOCALE, "en-us");

        Spark::Set(Config::STORAGE_CACHE_ENABLED, TRUE);

        Spark::Set(Config::PAGE_CACHE_ENABLED, FALSE);
        Spark::Set(Config::PAGE_CACHE_TTL, 86400);
        Spark::Set(Config::PAGE_CACHE_CLEANUP_DELTA, 3600);

        Spark::Set(Config::BEAN_CACHE_BACKEND, "filesystem");
        Spark::Set(Config::PAGE_CACHE_BACKEND, "filesystem");

        Spark::Set(Config::STORAGE_ITEM_SLUG, FALSE);

        Spark::Set(Config::SLUG_TRANSLITERATE, true);
        Spark::Set(Config::TRANSLITERATOR_ID, "Bulgarian-Latin/BGN");

        Spark::Set(Config::TIMEZONE, "Europe/Sofia");

        Spark::Set(Config::SITE_TITLE, "Default SparkBox Site");
    }

    final static public function CachePath() : string
    {

        $appFolder = basename(Spark::Get(Config::APP_PATH));
        $parentFolder = dirname(Spark::Get(Config::APP_PATH));
        $result = $parentFolder . DIRECTORY_SEPARATOR . "sparkcache" . DIRECTORY_SEPARATOR . $appFolder;

        if (!file_exists($result)) {
            Debug::ErrorLog("Creating app root cache folder: " . $result);
            @mkdir($result, 0777, TRUE);
            if (!file_exists($result)) throw new Exception("Unable to create cache folder: " . $result);
        }

        return $result;
    }

    /**
     * Assign global variable. If static is set value can be set only once.
     * @param string $name
     * @param string|float|int|bool $value
     * @param bool $isStatic
     * @return void
     * @throws Exception
     */
    final static public function Set(string $name, string|float|int|bool $value, bool $isStatic=false) : void
    {
        if (isset(Spark::$constDefines[$name])) {
            throw new Exception("Static '$name' already assigned");
        }
        Spark::$defines[$name] = $value;
        if ($isStatic) {
            Spark::$constDefines[$name] = true;
        }
    }

    final static public function Get(string $name, string $default="") : string|float|int|bool
    {
        if (isset(Spark::$defines[$name])) {
            return Spark::$defines[$name];
        }
        else return $default;

    }
    final static public function GetBoolean(string $name) : bool
    {
        $result = Spark::Get($name);
        return Marshall::Boolean($result);
    }
    final static public function GetString(string $name) : string
    {
        $result = Spark::Get($name);
        return Marshall::String($result);
    }
    final static public function GetInteger(string $name) : int
    {
        $result = Spark::Get($name);
        return Marshall::Integer($result);
    }
    final static public function GetFloat(string $name) : float
    {
        $result = Spark::Get($name);
        return Marshall::Float($result);
    }


    /**
     * Export each config variable as constant using define() function. Skip already defined.
     * @return void
     */
    final static public function DefineConfig() : void
    {
        foreach (Spark::$defines as $key => $val) {
            if (defined($key)) {
                continue;
            }
            define($key, $val);
        }
    }

    final static public function Dump() : void
    {
        foreach (Spark::$defines as $key => $val) {
            echo $key . "=>" . $val;
            echo "<BR>";
        }
    }

    /**
     * Non-cryptographic hashing
     * @param string $data
     * @param string $algorithm Default algorithm xxh3
     * @return string
     */
    final static public function Hash(string $data, string $algorithm="xxh3") : string
    {
        return hash('xxh3', $data);
    }

    final static public function Slugify(string $text) : string
    {
        // Convert to lowercase and normalize special characters
        $text = mb_strtolower($text, 'UTF-8');

        //if (SLUG_TRANSLITERATE){}
        // Transliterate non-ASCII characters to their ASCII equivalents
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower();', $text);

        // Remove any remaining special characters
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);

        // Replace spaces and multiple hyphens with single hyphen
        $text = preg_replace('/[\s-]+/', '-', trim($text));

        // Remove hyphens from start and end
        $text = trim($text, '-');

        return $text;
    }

    final static public function IsEmptyPassword($password) : bool
    {
        return (strcmp($password, "d41d8cd98f00b204e9800998ecf8427e") === 0);
    }

    /**
     * Escapes a string to be safe for HTML attribute values and element content.
     *
     * Reverses any prior custom escaping, then applies HTML5-safe escaping.
     * Safe to use in: attribute values (value="", data-*, title="") and between html tags lik <textarea>.
     *
     * @param string $value Input string (possibly previously escaped)
     * @return string       Escaped string safe for HTML output
     */
    final static public function AttributeValue(string $value) : string
    {
        return htmlspecialchars(Spark::Unescape($value), ENT_QUOTES | ENT_HTML5, "UTF-8");
    }


    /**
     * Reverses custom escaping of backslashes, quotes, and control characters.
     * Returns empty string if input is null.
     *
     * @param string|null $input   Input string to unescape
     * @param int         $checkbr Unused parameter (kept for compatibility)
     * @return string              Unescaped string
     */
    final static public function Unescape(?string $input, $checkbr = 0) : string
    {
        if (!is_null($input)) {
            $search = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
            $replace = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
            return str_replace($search, $replace, $input);
        }
        return "";
    }
    final static public function ReplaceTags(string $text, string $replacement=" ") : string
    {

        // ----- remove HTML TAGs -----
        $text = preg_replace ('/<[^>]*>/', $replacement, $text);

        // ----- remove control characters -----
        $text = str_replace("\r", $replacement, $text);    // --- replace with empty space
        $text = str_replace("\n", $replacement, $text);   // --- replace with space
        $text = str_replace("\t", $replacement, $text);   // --- replace with space

        // ----- remove multiple spaces -----
        $text = trim(preg_replace('/ {2,}/', $replacement, $text));

        return $text;

    }
    final static public function MetaDescription(string $value, int $max_length = -1) : string
    {

        $value = str_replace("\\r\\n", " ", $value);
        $value = Spark::ReplaceTags($value);
        $value = preg_replace("/[^\wA-Za-z0-9\-\%\?\!\;\:\.\, ]/u", "",$value);

        if ($max_length>0) {
            //cut to the exact size
            $value = mb_substr($value, 0, $max_length);
            //reverse to the last space
            $value = mb_substr($value, 0, mb_strrpos($value, " "));
        }
        return $value;
    }

    final static public function DeleteFolder(string $dirPath) : void
    {
        if (!is_dir($dirPath)) {
            throw new RuntimeException("$dirPath must be a directory");
        }
        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                Spark::DeleteFolder($file);
            }
            else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
    final static public function DateFormat(string $date, bool $time = TRUE) : string
    {
        $tm = "";
        if ($time) $tm = "H:i";

        return date("j F Y $tm", strtotime($date));
    }
    final static public function MicroTime() : float
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Check if key '$key' of the associative array '$arr' is set and have value equal to '$val'.
     * If $arr is NULL or not specified the $_GET array is used.
     * @param string $key
     * @param string $val
     * @param array|null $arr
     * @return bool
     */
    final static public function strcmp_isset(string $key, string $val, ?array $arr = NULL): bool
    {
        if (!$arr) $arr = $_GET;
        return (isset($arr[$key]) && (strcmp($arr[$key], $val) === 0));
    }

    final static public function HtmlStrip(string $data_str, string $allowable_tags = "<center><p><span><div><br><a>", array $allowable_attrs = array('href','src','alt','title')) : string
    {
        // define allowable tags
        // define allowable attributes

        // strip collector
        $strip_arr = array();

        // load XHTML with SimpleXML
        $data_sxml = simplexml_load_string('<root>'. $data_str .'</root>', 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOXMLDECL);

        if ($data_sxml instanceof SimpleXMLElement) {
            // loop all elements with an attribute
            foreach ($data_sxml->xpath('descendant::*[@*]') as $tag) {
                // loop attributes
                foreach ($tag->attributes() as $name=>$value) {
                    // check for allowable attributes
                    if (!in_array($name, $allowable_attrs)) {
                        // set attribute value to empty string
                        $tag->attributes()->$name = '';
                        // collect attribute patterns to be stripped
                        $strip_arr[$name] = '/ '. $name .'=""/';
                    }
                }
            }

            // strip unallowed attributes and root tag
            return strip_tags(preg_replace($strip_arr,array(''),$data_sxml->asXML()), $allowable_tags);
        }
        else {
            return $data_str;
        }

    }

    final static public function SanitizeInput(array|string $value, bool $allowHTML = false) : array|string
    {
        if (is_array($value)) return Spark::SafeArray($value, $allowHTML);
        return Spark::SafeValue($value, $allowHTML);
    }

    static private function SafeArray(array $arr, bool $allowHTML = false) : array
    {
        $safe_ret = array();
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $safe_ret[$key] = Spark::SafeArray($val, $allowHTML);
            }
            else {
                $safe_ret[$key] = Spark::SafeValue($val, $allowHTML);
            }
        }
        return $safe_ret;
    }

    private static function SafeValue(string $input, bool $allowHTML = false): string
    {
        $input = trim($input);

        $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $nohtml = strip_tags($input);
        //html content check
        if ($input !== $nohtml) {
            // HTML path: preserve allowed formatting, remove dangerous attributes
            if ($allowHTML) {
                include_once("utils/CleanHTML.php");
                $input = CleanHTML::Sanitize($input);
            }
            else {
                $input = $nohtml;
            }
        }

        if (Spark::GetBoolean(Config::DB_ENABLED)) {
            return DBConnections::Open()->escape($input);
        }
        else {
            return Spark::EscapeHelper($input);
        }
    }

    static private function EscapeHelper(string $unescaped_string): string
    {
        $replacementMap = [
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "\t" => "\\t",
            chr(26) => "\\Z",
            chr(8) => "\\b",
            '"' => '\"',
            "'" => "\'",
            '_' => "\_",
            "%" => "\%",
            '\\' => '\\\\'
        ];

        return strtr($unescaped_string, $replacementMap);
    }

    final static public function ByteLabel($size) : string
    {
        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }


    final static public function Base64URLDecode($input) : false|string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    static private function SiteTitleArray(array $path) : array
    {
        $path = array_reverse($path);
        $title = array();
        foreach ($path as $key => $item) {
            if ($item instanceof MenuItem) {
                $title[] = mb_convert_case(strip_tags($item->getName()), MB_CASE_TITLE, "UTF-8");
            }
            else {
                $title[] = mb_convert_case($item, MB_CASE_TITLE, "UTF-8");
            }
        }
        return $title;
    }

    final static public function SiteTitle(array $path) : string
    {
        $title = Spark::SiteTitleArray($path);

        return implode(Spark::Get(Config::TITLE_PATH_SEPARATOR), $title);
    }

    final static public function isStorageRequest() : bool
    {
        return isset($_GET["StorageRequest"]) || (strcasecmp(Spark::RequestScript(), "storage.php")===0);
    }

    final static public function isJSONRequest() : bool
    {
        return isset($_REQUEST[Config::KEY_JSON_REQUEST]);
    }

    final static public function RequestScript() : string
    {
        return isset($_SERVER['SCRIPT_FILENAME'])
            ? basename($_SERVER['SCRIPT_FILENAME'])
            : 'unknown';
    }

    final static public function Split(string $text, string $separator="/"): array
    {
        // Split by / and filter out empty elements
        $parts = array_filter(
            explode($separator, $text),
            fn($segment) => $segment !== ''
        );

        // Re-index the array (optional but cleaner)
        return array_values($parts);
    }

    final static public function ClassChain(object $object) : array
    {
        $class_chain = class_parents($object, false);
        array_pop($class_chain);
        $class_chain = array_reverse($class_chain);
        $class_chain[] = get_class($object);
        return $class_chain;
    }
    //static public function parse_signed_request($signed_request, $secret)
    //{
    //    list($encoded_sig, $payload) = explode('.', $signed_request, 2);
    //
    //    // decode the data
    //    $sig = base64_url_decode($encoded_sig);
    //    $data = json_decode(base64_url_decode($payload), TRUE);
    //
    //    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
    //        // 	  error_log('Unknown algorithm. Expected HMAC-SHA256');
    //        throw new Exception('Unknown algorithm. Expected HMAC-SHA256');
    //        // 	  return null;
    //    }
    //
    //    // check sig
    //    $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = TRUE);
    //    if ($sig !== $expected_sig) {
    //        //     error_log('Bad Signed JSON signature!');
    //        throw new Exception("Bad Signed JSNO signature!");
    //        //     return null;
    //    }
    //
    //    return $data;
    //}
}