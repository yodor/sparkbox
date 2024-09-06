<?php

/**
 * Non-cryptographic hashing
 * @param string $data
 * @return string
 */
function sparkHash(string $data) : string
{
    return hash('xxh3', $data);
}

function sanitizeKeywords(string $text) : string
{
    $keywords = trim(mb_strtolower($text));
    if (mb_strlen($keywords)<1) return "";

    $keywords = str_replace("етикети: ", "", $keywords);
    $keywords = str_replace("етикет: ", "", $keywords);

    $keywords = preg_replace("/\\r\\n/", ", ", $keywords);
    //!!
    $keywords = str_replace("\\r\\n", ", ", $keywords);

    $keywords_all = explode(",", $keywords);

    foreach ($keywords_all as $idx=>$item) {
        $item = trim($item);
        if (mb_strlen($item)>0) {
            $keywords_all[$idx] = $item;
        }
        else {
            unset($keywords_all[$idx]);
        }
    }
    $keywords = implode(", ", $keywords_all);

    return $keywords;
}

function slugify(string $text) : false|string
{

    $rules = <<<'RULES'
    :: Any-Latin;
    :: NFD;
    :: [:Nonspacing Mark:] Remove;
    :: NFC;
    :: [^-[:^Punctuation:]] Remove;
    :: Lower();
    [:^L:] { [-] > ;
    [-] } [:^L:] > ;
    [-[:Separator:]]+ > '-';
RULES;

    return Transliterator::createFromRules($rules)->transliterate($text);
}

function transliterate(string $text) : false|string
{
    $id = "Bulgarian-Latin/BGN";
    if (defined("TRANSLITERATOR_ID")) {
        $id = TRANSLITERATOR_ID;
    }
    $result = Transliterator::create($id)->transliterate($text);
    $result = slugify($result);
    return $result;
}

function enclose(string $value, string $char = "'") : string
{
    return $char.$value.$char;
}
//extract src attribute value from google maps embed HTML
function googleMapURL($iframe_html)
{
    $data_sxml = simplexml_load_string('<root>'. $iframe_html .'</root>', 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOXMLDECL);

    if ($data_sxml ) {
        // loop all elements with an attribute
        foreach ($data_sxml->xpath('descendant::*[@*]') as $tag) {
            // loop attributes
            foreach ($tag->attributes() as $name=>$value) {
                if (strcmp($name, "src")===0) {
                    return $value;
                }
            }
        }
    }

    return $iframe_html;
}

function prepareMeta(string $value, int $max_length = -1)
{

    $value = str_replace("\\r\\n", " ", $value);
    $value = replace_tags($value);
    $value = preg_replace("/[^\wA-Za-z0-9\-\%\?\!\;\:\.\, ]/u", "",$value);

    if ($max_length>0) {
        //cut to the exact size
        $value = mb_substr($value, 0, $max_length);
        //reverse to the last space
        $value = mb_substr($value, 0, mb_strrpos($value, " "));
    }
    return $value;
}

function replace_tags(string $text, string $replacement=" ") : string {

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

function stripAttributes(string $data_str, string $allowable_tags = "<center><p><span><div><br><a>", array $allowable_attrs = array('href','src','alt','title')) : string
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
function currentURL(): string
{
    $ret = $_SERVER["SCRIPT_NAME"];
    if ($_SERVER["QUERY_STRING"]) {
        $ret .= "?" . rawurldecode($_SERVER["QUERY_STRING"]);
    }
    return $ret;
}

function fullURL(string $url) : string
{
    return SITE_PROTOCOL.SITE_DOMAIN.$url;
}

function normalize($str)
{
    $content = htmlentities($str, NULL, 'utf-8');
    $content = str_replace("&nbsp;", " ", $content);
    $content = str_replace("\r\n", "<BR>", $content);
    $content = str_replace("\n", "<BR>", $content);
    $content = str_replace("\r", "<BR>", $content);
    $content = html_entity_decode($content);
    return $content;
}

function quoteArray(&$item, $key, $user_data = "")
{
    $item = "'" . $item . "'";
}

function KMG(&$umf)
{
    if (str_contains($umf, "M")) {
        str_replace("M", "", $umf);
        $umf = (int)$umf * 1024 * 1024;
    }
    else if (str_contains($umf, "K")) {
        str_replace("K", "", $umf);
        $umf = (int)$umf * 1024;
    }
}

function file_size($size)
{
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
}

function renderExceptionDetails($error)
{

    $details = "<a class='exception_link' onClick='javascript:showExceptonDetails(this)'>";
    $details .= tr("Error Details");
    $details .= "</a>";
    $details .= "<div class='exception_details'>";
    $details .= $error;
    $details .= "</div>";
    return $details;

}

function constructSiteTitleArray($path) : array
{
    $path = array_reverse($path);
    $title = array();
    foreach ($path as $key => $item) {
        if ($item instanceof MenuItem) {
            $title[] = mb_convert_case(strip_tags($item->getTitle()), MB_CASE_TITLE, "UTF-8");
        }
        else {
            $title[] = mb_convert_case($item, MB_CASE_TITLE, "UTF-8");
        }
    }
    return $title;
}

function constructSiteTitle($path)
{
    $title = constructSiteTitleArray($path);

    return implode(TITLE_PATH_SEPARATOR, $title);
}

function getArrayText(array $arr)
{

    $msg = array();
    foreach ($arr as $key => $val) {
        $message = "";
        if ($val instanceof StorageObject) {
            $message = get_class($val) . " UID:" . $val->UID() . " MIME: ".$val->buffer()->mime();
            if ($val instanceof ImageStorageObject) {
                $message.= " Dimension: (" . $val->getWidth() . "," . $val->getHeight() . ")";
            }
        }
        else if (is_array($val)) {
            $message = print_r($val, true);
        }
        else if (is_object($val)) {
            $message = get_class($val);
        }
        else if (is_null($val)) {
            $message = "NULL";
        }
        $msg[] = "[$key] => $message";
    }
    return implode("; ", $msg);

}

function debug($obj, $msg = NULL, $arr = NULL)
{
    if (!(isset($GLOBALS["DEBUG_OUTPUT"]) && $GLOBALS["DEBUG_OUTPUT"])) return;

    $class = "";
    $message = "";
    $array = array();

    if (is_object($obj)) {
        $class = get_class($obj);
        $message = $msg;
        $array = $arr;
    }
    else {
        $message = $obj;
        $array = $msg;
    }

    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);

    $first = $bt[0];
    $parent = $first;

    $file = basename($first["file"]);

    if (count($bt) > 0) {
        //$last = $bt[count($bt)-1];

        $index = 1;

        while ($index < count($bt)) {
            $parent = $bt[$index];
            if (isset($parent["class"])) {
                break;
            }
            $index++;
        }
    }

    $last = $bt[count($bt) - 1];

    $url = basename($_SERVER['SCRIPT_FILENAME']);
    if (isset($last["file"])) {
        $url = basename($last["file"]);
    }

    $line = $first["line"];

    $file2 = $url;
    if (isset($parent["file"])) {
        $file2 = basename($parent["file"]);
    }
    $line2 = 0;
    if (isset($parent["line"])) {
        $line2 = $parent["line"];
    }
    else if (isset($first["line"])) {
        $line2 = $first["line"];
    }

    if (strlen($class) < 1) {
        if (isset($parent["class"])) {
            $class = $parent["class"];
        }
    }
    $function = $parent["function"];

    $time = "";
    if (isset($GLOBALS["DEBUG_OUTPUT_MICROTIME"])) {
        $time = microtime_float();
    }

    if (is_array($array)) {
        $message .= " " . getArrayText($array);
    }

    error_log("$time $url [$file2:$line2] [$class::$function] $message");
}

function keywordFilterSQL($keywords_text, $search_fields, $inner_glue = " OR ", $outer_glue = " AND ", $split_string = "/[,;]/")
{
    $db = DBConnections::Get();

    if (strlen($keywords_text) < 1) throw new Exception("keywords_text parameter empty");
    if (!is_array($search_fields) && strlen($search_fields) < 1) throw new Exception("search_fields parameter empty");
    if (is_array($search_fields) && count($search_fields) < 1) throw new Exception("search_fields parameter empty");

    $keywords = preg_split($split_string, $keywords_text);
    $kwsearch = array();
    foreach ($keywords as $key => $word) {
        $word = $db->escape(trim($word));
        if (!is_array($search_fields)) {
            $search_fields = array($search_fields);
        }
        $sfields = array();
        for ($a = 0; $a < count($search_fields); $a++) {
            $field_name = $search_fields[$a];
            $sfields[] = " ( $field_name LIKE '%$word%' ) ";
        }
        $kwsearch[] = " ( " . implode($inner_glue, $sfields) . " ) ";

        //       $kwsearch[] = " ((summary LIKE '%$word%') OR ( item_title LIKE '%$word%' ) OR ( location LIKE '%$word%' ) OR ( venue LIKE '%$word%' ) OR (tags LIKE '%$word%')";
    }
    $kwsearch = implode($outer_glue, $kwsearch);

    return $kwsearch;

}

function queryArray()
{
    return $_GET;
}

//take query array and return string
function queryString($qryarr = 0, $append = "")
{
    $ret = "";
    $workarr = $_GET;
    if (is_array($qryarr)) {
        $workarr = $qryarr;
    }
    if (count($workarr) > 0) {
        $qrypair = array();
        foreach ($workarr as $key => $val) {
            if (strlen($val) > 0) {
                $qrypair[] = $key . "=" . $val;
            }
            else {
                $qrypair[] = $key;
            }
        }
        $ret = "?" . implode("&", $qrypair);
        if (strlen($append) > 0) {
            $append = str_replace("?", "", $append);
            $ret .= "&" . $append;
        }
    }
    else if (strlen($append) > 0) {
        $append = str_replace("?", "", $append);
        $ret .= "?" . $append;
    }

    return $ret;

}

function urlString($str)
{
    $pos = strpos("?", $str);
    if ($pos > 0) {
        $str = substr(0, $pos);
    }
    return $str;
}

function stringQuery($str)
{
    $pos = strpos("?", $str);
    if ($pos > 0) {
        $str = substr($str, $pos + 1);
    }
    $pairs = explode("&", $str);
    $ret = array();
    foreach ($pairs as $pos => $val) {
        $vals = explode("=", $val);
        $keyval = "";
        if (isset($vals[1])) $keyval = $vals[1];
        $ret[$vals[0]] = $keyval;
    }
    return $ret;
}

function isEmptyPassword($password)
{
    return (strcmp($password, "d41d8cd98f00b204e9800998ecf8427e") == 0);
}

//TODO: move field2columns to parsing class
function fields2columns(InputForm $form, TableView $view, IDataBean $bean = NULL)
{
    foreach ($form->getInputs() as $field_name => $field) {
        if ($bean) {
            if (!$bean->haveField($field_name)) continue;
        }

        $view->addColumn(new TableColumn($field_name, $field_name));

    }
}

function reorderArray(array $values_array)
{
    $values_ordered = array();

    $idx = -1;
    foreach ($values_array as $key => $value) {

        $idx++;
        $values_ordered[$idx] = $value;
    }

    return $values_ordered;
}

function DefaultAcceptedTags()
{

    return "<br><p><a><ul><ol><li><b><u><i><h1><h2><h3><h4><center><sub><sup><hr><img><object><video><embed><iframe><strong><em><span>";

}

function sanitizeInput($value, $accepted_tags = NULL)
{
    if (is_array($value)) return safeArray($value, $accepted_tags);
    return safeVal($value, $accepted_tags);
}

function safeArray($arr, $accepted_tags = NULL)
{
    if (is_null($accepted_tags)) $accepted_tags = DefaultAcceptedTags();

    $safe_ret = array();
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $safe_ret[$key] = safeArray($val, $accepted_tags);
        }
        else {

            $safe_ret[$key] = safeVal($val, $accepted_tags);

        }

    }

    return $safe_ret;

}

//if get_magic_quotes_gpc();         // 1 then post data is already escaped
function safeVal($val, $accepted_tags = NULL)
{
    if (is_null($accepted_tags)) $accepted_tags = DefaultAcceptedTags();

    $ret = strip_tags(html_entity_decode(stripslashes(trim($val))), $accepted_tags);

    if (DBConnections::count()>0) {
        return DBConnections::Get()->escape($ret);
    }
    else {
        return escapeHelper($ret);
    }
}

function escapeHelper(string $unescaped_string): string
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

function attributeValue(string $value)
{
    return htmlentities(mysql_real_unescape_string($value), ENT_QUOTES, "UTF-8");
}

function json_string($text)
{
    if (defined("JSON_UNESCAPED_UNICODE")) {
        return json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
    else {
        return json_encode(utf8_encode($text));
    }
}

function listFiles($dir, $callback)
{
    $all_files = scandir($dir);
    foreach ($all_files as $pos => $file) {
        if (strcmp($file, ".") == 0) continue;
        if (strcmp($file, "..") == 0) continue;
        if (is_dir($file)) continue;
        call_user_func($callback, $file);
    }
}

function parse_signed_request($signed_request, $secret)
{
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);

    // decode the data
    $sig = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), TRUE);

    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
        // 	  error_log('Unknown algorithm. Expected HMAC-SHA256');
        throw new Exception('Unknown algorithm. Expected HMAC-SHA256');
        // 	  return null;
    }

    // check sig
    $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = TRUE);
    if ($sig !== $expected_sig) {
        //     error_log('Bad Signed JSON signature!');
        throw new Exception("Bad Signed JSNO signature!");
        //     return null;
    }

    return $data;
}

function base64_url_decode($input)
{
    return base64_decode(strtr($input, '-_', '+/'));
}

function keywordSplit($str, $pattern = '/[; ,]/')
{

    $kwall = preg_split($pattern, $str);
    $search_words = array();
    foreach ($kwall as $key => $val) {
        if (strlen(trim($val)) > 0) $search_words[] = $val;
    }
    return $search_words;
}

function array2object($array)
{

    if (is_array($array)) {
        $obj = new StdClass();

        foreach ($array as $key => $val) {
            $obj->$key = $val;
        }
    }
    else {
        $obj = $array;
    }

    return $obj;
}

function object2array($object)
{
    $arr = array();
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $arr[$key] = $value;
        }
    }
    else {
        $arr = $object;
    }
    return $arr;
}

function mysql_real_unescape_string(?string $input, $checkbr = 0) : string
{

    // mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', " and \x1a.

    // $output = $input;
    // $output = str_replace("\\\\", "\\", $output);
    // $output = str_replace("\'", "'", $output);
    // $output = str_replace('\"', '"', $output);
    if (!is_null($input)) {
        $search = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
        $replace = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
        return str_replace($search, $replace, $input);
    }
    return "";
}

function text4div(&$text)
{
    return str_replace("\n", "<BR>", $text);
}

/**
 * Check if key '$key' of the associative array '$arr' is set and have value equal to '$val'.
 * If $arr is NULL or not specified the $_GET array is used.
 * @param string $key
 * @param string $val
 * @param array|null $arr
 * @return bool
 */
function strcmp_isset(string $key, string $val, array $arr = NULL): bool
{
    if (!$arr) $arr = $_GET;
    return (isset($arr[$key]) && (strcmp($arr[$key], $val) == 0));
}

function CalculateAge($date)
{
    $unix_date = strtotime($date);
    $year = date("Y");
    $year_birth = date("Y", $unix_date);

    $adjust = 0;

    $thatyear = strtotime(date("Y", $unix_date) . "-" . date("m") . "-" . date("d"));
    if ($thatyear < $unix_date) {
        $adjust = 1;
    }

    return (($year - $year_birth) - $adjust);

}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function isOnline($last_seen_date)
{
    if (strcmp($last_seen_date, "0000-00-00 00:00:00") == 0) {
        return FALSE;
    }
    $last_seen = strtotime($last_seen_date);
    $now = strtotime("now");
    $diff = $now - $last_seen;

    $session_lifetime = ini_get("session.gc_maxlifetime");

    if ($diff >= $session_lifetime) {
        return FALSE;
    }
    else {
        return TRUE;
    }

}

function NiceTime($date)
{
    if (empty($date)) {
        return "No date provided";
    }
    if (strcmp($date, "0000-00-00 00:00:00") == 0) {
        return "Never";
    }
    $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

    $now = time();
    $unix_date = strtotime($date);

    // check validity of date
    if (empty($unix_date)) {
        return "Bad date";
    }

    // is it future date or past date
    if ($now > $unix_date) {
        $difference = $now - $unix_date;
        $tense = "ago";

    }
    else {
        $difference = $unix_date - $now;
        $tense = "from now";
    }

    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);

    if ($periods[$j] == "second" && $difference == 0) {
        return "Now";
    }

    if ($difference != 1) {
        $periods[$j] .= "s";
    }

    return "$difference $periods[$j] {$tense}";
}

function StarsForValue($val, $min = 1, $max = 5)
{
    $on_star = "<img class='star star_on'  src='" . SPARK_LOCAL . "/images/icon_star_on.png'>";
    $half_star = "<img class='star star_half' src='" . SPARK_LOCAL . "/images/icon_star_half.png'>";
    $off_star = "<img class='star star_off' src='" . SPARK_LOCAL . "/images/icon_star_off.png'>";

    // 	$val = ($val/10);

    for ($a = $min; $a <= $max; $a++) {
        if ($val >= $a) {
            echo $on_star;
        }
        else if ($val < $a && $val > ($a - 1)) {
            echo $half_star;
        }
        else {
            echo $off_star;
        }
    }
}

function timestamp2mysqldate($timestamp)
{
    return date("Y-m-d H:i:s", $timestamp);

}

function dateFormat($date, $time = TRUE)
{
    $tm = "H:i";
    if (!$time) $tm = "";
    return date("j F Y $tm", strtotime($date));
}

function dateFormatFromUnix($unixtime, $time = TRUE)
{
    $tm = "H:i";
    if (!$time) $tm = "";
    return date("j F Y $tm", $unixtime);
}

function date2time($date, $format = '%a, %d %b %Y %H:%M:%S %z')
{

    $arr = strptime($date, $format);

    $rhour = (int)$arr["tm_hour"];
    $rminute = (int)$arr["tm_min"];
    $rsecond = (int)$arr["tm_sec"];
    $rmonth = (1 + (int)$arr["tm_mon"]);
    $rday = (int)$arr["tm_mday"];
    $ryear = (1900 + (int)$arr["tm_year"]);
    return mktime($rhour, $rminute, $rsecond, $rmonth, $rday, $ryear);
}

function dumpVal($val)
{
    $order = array("\r\n", "\n", "\r");
    $replace = '<br />';
    // Processes \r\n's first so they aren't converted twice.
    $newstr = str_replace($order, $replace, $val);
    return $newstr;
}

function filterText(&$content, $length)
{

    if (strlen($content) <= $length) return $content;
    // return $content;

    $finished = 0;
    $chars_so_far = 0;
    while ($finished == 0) {
        $pos = strpos($content, " ", ($chars_so_far + 1));
        if ($pos > -1) {
            $chars_so_far = $pos;
        }
        else {
            $chars_so_far = $length;
            break;
        }
        if ($chars_so_far >= $length) {
            $finished = 1;
        }
    }
    $cnt_small = substr($content, 0, ($chars_so_far)) . " ...";
    return $cnt_small;
}

function unset_multi(&$arr, $fields)
{
    foreach ($fields as $key => $val) {

        if (isset($arr[$val])) unset($arr[$val]);
    }
}

function deleteDir(string $dirPath) : void
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
            deleteDir($file);
        }
        else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

?>
