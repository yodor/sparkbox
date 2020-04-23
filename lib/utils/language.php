<?php
include_once("lib/beans/LanguagesBean.php");
include_once("lib/beans/SiteTextsBean.php");
include_once("lib/beans/SiteTextUsageBean.php");


include_once("lib/beans/TranslationBeansBean.php");
include_once("lib/beans/TranslationPhrasesBean.php");
include_once("lib/utils/Session.php");


//TODO: check usage when this file is included from js.php files that include the main/top session.php

$g_sp = new SiteTextsBean();
$g_stu = new SiteTextUsageBean();
$g_lb = new LanguagesBean();
$g_tr = new TranslationPhrasesBean();
$g_bt = new TranslationBeansBean();

function setLanguageString(string $str, $page_dir = "LTR")
{
    global $g_lb;


    if (strcmp($str, "arabic") == 0) {
        $page_dir = "RTL";
    }

    try {
        $langID = $g_lb->id4language($str);
    }
    catch (Exception $e) {
        $langrow = $g_lb->getByID(1);
        $langID = 1;
        $str = $langrow["language"];
    }
    setLanguage($str, $langID, $page_dir);
}

function setLanguageID(int $langID, $page_dir = "LTR")
{

    global $g_lb;

    try {
        $langrow = $g_lb->getByID($langID);
        $str = $langrow["language"];
    }
    catch (Exception $e) {
        setLanguageID(1);
        return;
    }
    setLanguage($str, $langID, $page_dir);
}

function setLanguage(string $str, int $langID, $page_dir)
{
    Session::Set("language", "$str");
    Session::Set("langID", $langID);
    if (strcmp($page_dir, "RTL") == 0) {
        Session::Set("page_dir", "RTL");
        Session::Set("left", "right");
        Session::Set("right", "left");
    }
    else {
        Session::Set("page_dir", "LTR");
        Session::Set("left", "left");
        Session::Set("right", "right");
    }
    Session::SetCookie("language", "$str");
    Session::SetCookie("langID", "$langID");
}


//sane defaults
if (!Session::Contains("language") || !Session::Contains("langID")) {
    if (Session::HaveCookie("langID")) {
        $langID = Session::GetCookie("langID");
        setLanguageID($langID);
    }
    else {
        //set default language
        setLanguageString(DEFAULT_LANGUAGE);
    }
}


$reload = false;


if (isset($_GET["change_language"]) && isset($_GET["language"])) {
    $lng = $_GET["language"];
    setLanguageString($lng);
    $reload = true;
}
else if (isset($_GET["change_language"]) && isset($_GET["langID"])) {
    $langID = (int)$_GET["langID"];
    setLanguageID($langID);
    $reload = true;
}

if ($reload === true) {
    $qarr = $_GET;

    if (isset($qarr["language"])) unset($qarr["language"]);
    if (isset($qarr["langID"])) unset($qarr["langID"]);
    if (isset($qarr["change_language"])) unset($qarr["change_language"]);
    if (isset($qarr["page_dir"])) unset($qarr["page_dir"]);
    $qstr = queryString($qarr);
    // 				if (strlen($qstr)>0)$qstr="?".$qstr;
    header("Location: " . $_SERVER['PHP_SELF'] . $qstr);
    exit;

}
// 
$left = Session::Get("left");
$right = Session::Get("right");
$language = Session::Get("language");
$languageID = Session::Get("langID");

function getActiveLanguageID()
{
    global $g_lb;

    $langID = 1;

    $lang_session = Session::Get("language", DEFAULT_LANGUAGE);
    $lang = DBDriver::Get()->escapeString($lang_session);

    $num = $g_lb->startIterator("WHERE language='$lang'");
    if ($g_lb->fetchNext($lrow)) {
        $langID = $lrow["langID"];
    }
    else {
        //language not in database. return default text
        //return $str;
        setLanguageString(DEFAULT_LANGUAGE);
        throw new Exception("Language not in database: $lang");
    }
    return $langID;
}

function trbean(int $id, string $field_name, array &$row, DBTableBean $bean)
{
    $lang_session = Session::Get("language", DEFAULT_LANGUAGE);
    global $g_bt;

    $langID = getActiveLanguageID();


    //if ($langID==1) return;
    $table_name = $bean->getTableName();

    // 		$keyid = $bean->getPrKey();
    $num = $g_bt->startIterator("WHERE langID='$langID' AND field_name='$field_name' AND table_name='$table_name' AND bean_id='$id' LIMIT 1", " translated ");

    if ($g_bt->fetchNext($btrow)) {
        $row[$field_name] = $btrow["translated"];
    }


}

/**
 * @param string $str_original
 * @return string translated version of $str_original
 */
function tr(string $str_original)
{

    if (strlen(trim($str_original)) == 0) return $str_original;


    global $g_sp, $g_stu, $g_tr, $g_lb;


    $str = DBDriver::Get()->escapeString($str_original);

    try {

        // 		$bt = debug_backtrace();
        // 		//store call trace to ease deletion of old phrases
        // 		//var_dump($bt[0]);
        // 		$caller = $bt[0];
        // 		unset($caller["function"]);
        // 		unset($caller["args"]);
        // 		$usedby = implode("|",$caller);

        $usedby = " ";

        $langID = -1;

        $textID = $g_sp->id4phrase($str_original);

        $sturow["usedby"] = $usedby;
        $sturow["textID"] = $textID;

        // 		$g_stu->insertRecord($sturow);

        $langID = getActiveLanguageID();

        //do not try to translate english
        //if ($langID==1)return $str_original;

        $num = $g_tr->startIterator("WHERE langID=$langID and textID=$textID");
        if ($num) {
            if ($g_tr->fetchNext($trow)) {
                 return (string)$trow["translated"];
            }
            else {
                throw new Exception("DBError: Translation can not be fetch from table. " . $g_tr->getError());
            }
        }
        else {
            // 			throw new Exception("This phrase is not yet translated to the requested language");
            return $str_original;
        }
    }
    catch (Exception $e) {
        //
        return $e->getMessage();

    }
    return $str_original;
}

function trnum($val)
{
    $language = Session::Get("language");
    if (strcmp($language, "arabic") == 0) {
        $arnum = array("0" => "٠", "1" => "١", "2" => "٢", "3" => "٣", "4" => "٤", "5" => "٥", "6" => "٦", "7" => "٧", "8" => "٨", "9" => "٩", "." => ".", "," => ",");
        $ret = "";

        for ($a = 0; $a < strlen($val); $a++) {
            $c = substr($val, $a, 1);
            if (isset($arnum[$c])) {
                $ret .= $arnum[$c];
            }
            else {
                $ret .= $c;
            }
        }
        return $ret;
    }
    return $val;
}

?>
