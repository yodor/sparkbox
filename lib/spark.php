<?php
final class Marshall {
    static public function String(mixed $value): string
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

    static public function Boolean(mixed $value): bool
    {
        if (is_string($value)) {
            $value = trim(strtolower($value));
            if (in_array($value, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }
    static public function Float(mixed $value): float
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

    static public function Integer(mixed $value): int
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
    static public function FromByteLabel(string $umf) : int
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
    static array $beanLocations = array();

    private function __construct()
    {}

    static public function Initialize(string $install_path) : void
    {

        Spark::Set(Config::INSTALL_PATH, $install_path, true);

        //static values can not be overwritten
        //$doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', realpath($_SERVER['SCRIPT_FILENAME']));
        //$location = preg_replace("!^${doc_root}!", '', $install_path);
        $doc_full = realpath($_SERVER['DOCUMENT_ROOT']);
        $location = preg_replace("!^{$doc_full}!", "", $install_path);

        Spark::Set(Config::LOCAL, $location, true);
        Spark::Set(Config::SPARK_LOCAL, $location . "/sparkfront", true);

        Spark::Set(Config::ADMIN_LOCAL, $location . "/admin", true);
        Spark::Set(Config::STORAGE_LOCAL, $location . "/storage.php", true);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        Spark::Set(Config::SITE_PROTOCOL, $protocol, true);

        $site_domain = $_SERVER["HTTP_HOST"];
        Spark::Set(Config::SITE_DOMAIN, $site_domain, true);

        Spark::Set(Config::SITE_URL, $protocol . $site_domain . $location, true);

        Spark::Set(Config::COOKIE_DOMAIN, ".".$site_domain, true); // or .domain.com

        //$umf = Marshall::FromByteLabel(ini_get("upload_max_filesize"));
        Spark::Set(Config::UPLOAD_MAX_SIZE, Marshall::FromByteLabel(ini_get("post_max_size")), true);
        Spark::Set(Config::MEMORY_LIMIT, Marshall::FromByteLabel(ini_get("memory_limit")), true);
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

    static public function CachePath() : string
    {

        $result = dirname(Spark::Get(Config::INSTALL_PATH)) . DIRECTORY_SEPARATOR . "sparkcache" . DIRECTORY_SEPARATOR . Spark::Get(Config::SITE_TITLE);

        if (!file_exists($result)) {
            Debug::ErrorLog("Creating cache folder: " . $result);
            @mkdir($result, 0777, TRUE);
            if (!file_exists($result)) throw new Exception("Unable to create cache folder: " . $result);
        }

        return $result;
    }

    static public function EnableBeanLocation(string $class_location, bool $enabled=true) : void
    {
        Spark::$beanLocations[$class_location] = $enabled;
    }

    /**
     * Load class definition
     * @param string $class_name
     * @return void
     * @throws Exception
     */
    static public function LoadBeanClass(string $class_name) : void
    {
        Debug::ErrorLog("Including bean class: $class_name");
        foreach (Spark::$beanLocations as $location => $enabled) {
            if (!$enabled) continue;
            $class_file = $location . $class_name . ".php";
            Debug::ErrorLog("Trying file: ".$class_file);
            @include_once($class_file);
            if (class_exists($class_name, FALSE)) {
                Debug::ErrorLog("Class load success");
                break;
            }
        }

        if (!class_exists($class_name, FALSE)) {
            Debug::ErrorLog("Class load failed");
            throw new Exception("Bean class not found: " . $class_name);
        }
    }

    /**
     * Assign global variable. If static is set value can be set only once.
     * @param string $name
     * @param string|float|int|bool $value
     * @param bool $isStatic
     * @return void
     * @throws Exception
     */
    static public function Set(string $name, string|float|int|bool $value, bool $isStatic=false) : void
    {
        if (isset(Spark::$constDefines[$name])) {
            throw new Exception("Static '$name' already assigned");
        }
        Spark::$defines[$name] = $value;
        if ($isStatic) {
            Spark::$constDefines[$name] = true;
        }
    }

    static public function Get(string $name, string $default="") : string|float|int|bool
    {
        if (isset(Spark::$defines[$name])) {
            return Spark::$defines[$name];
        }
        else return $default;

    }
    static public function GetBoolean(string $name) : bool
    {
        $result = Spark::Get($name);
        return Marshall::Boolean($result);
    }
    static public function GetString(string $name) : string
    {
        $result = Spark::Get($name);
        return Marshall::String($result);
    }
    static public function GetInteger(string $name) : int
    {
        $result = Spark::Get($name);
        return Marshall::Integer($result);
    }
    static public function GetFloat(string $name) : float
    {
        $result = Spark::Get($name);
        return Marshall::Float($result);
    }


    /**
     * Export each config variable as constant using define() function. Skip already defined.
     * @return void
     */
    static public function DefineConfig() : void
    {
        foreach (Spark::$defines as $key => $val) {
            if (defined($key)) {
                continue;
            }
            define($key, $val);
        }
    }

    static public function Dump() : void
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
    static public function Hash(string $data, string $algorithm="xxh3") : string
    {
        return hash('xxh3', $data);
    }

    static public function Slugify(string $text) : string
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

    static public function IsEmptyPassword($password) : bool
    {
        return (strcmp($password, "d41d8cd98f00b204e9800998ecf8427e") == 0);
    }

    static public function AttributeValue(string $value) : string
    {
        return htmlentities(Spark::Unescape($value), ENT_QUOTES, "UTF-8");
    }
    static public function Unescape(?string $input, $checkbr = 0) : string
    {
        if (!is_null($input)) {
            $search = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
            $replace = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
            return str_replace($search, $replace, $input);
        }
        return "";
    }
    static public function ReplaceTags(string $text, string $replacement=" ") : string
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
    static public function MetaDescription(string $value, int $max_length = -1) : string
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

    static public function DeleteFolder(string $dirPath) : void
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
    static public function DateFormat(string $date, bool $time = TRUE) : string
    {
        $tm = "";
        if ($time) $tm = "H:i";

        return date("j F Y $tm", strtotime($date));
    }
    static public function MicroTime() : float
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
    static public function strcmp_isset(string $key, string $val, ?array $arr = NULL): bool
    {
        if (!$arr) $arr = $_GET;
        return (isset($arr[$key]) && (strcmp($arr[$key], $val) == 0));
    }

    static public function HtmlStrip(string $data_str, string $allowable_tags = "<center><p><span><div><br><a>", array $allowable_attrs = array('href','src','alt','title')) : string
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

    static public function DefaultAcceptedTags() : string
    {
        return "<br><p><a><ul><ol><li><b><u><i><h1><h2><h3><h4><h5><h6><center><sub><sup><hr><img><object><video><embed><iframe><strong><em><span>";
    }

    static public function SanitizeInput(array|string $value, $accepted_tags = NULL) : array|string
    {
        if (is_array($value)) return Spark::SafeArray($value, $accepted_tags);
        return Spark::SafeValue($value, $accepted_tags);
    }

    static private function SafeArray($arr, $accepted_tags = NULL) : array
    {
        if (is_null($accepted_tags)) $accepted_tags = Spark::DefaultAcceptedTags();

        $safe_ret = array();
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $safe_ret[$key] = Spark::SafeArray($val, $accepted_tags);
            }
            else {

                $safe_ret[$key] = Spark::SafeValue($val, $accepted_tags);

            }

        }

        return $safe_ret;

    }

//if get_magic_quotes_gpc();         // 1 then post data is already escaped
    static private function SafeValue($val, $accepted_tags = NULL) : string
    {
        if (is_null($accepted_tags)) $accepted_tags = Spark::DefaultAcceptedTags();

        $ret = strip_tags(html_entity_decode(stripslashes(trim($val))), $accepted_tags);

        if (DBConnections::Count()>0) {
            return DBConnections::Open()->escape($ret);
        }
        else {
            return Spark::EscapeHelper($ret);
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

    static public function ByteLabel($size) : string
    {
        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }


    static public function Base64URLDecode($input) : false|string
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

    static public function SiteTitle(array $path) : string
    {
        $title = Spark::SiteTitleArray($path);

        return implode(Spark::Get(Config::TITLE_PATH_SEPARATOR), $title);
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