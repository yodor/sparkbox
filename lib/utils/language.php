<?php
include_once("beans/LanguagesBean.php");
include_once("beans/SiteTextsBean.php");

include_once("beans/BeanTranslationsBean.php");
include_once("beans/TranslationPhrasesBean.php");

include_once("utils/IRequestProcessor.php");
include_once("utils/URLBuilder.php");
include_once("utils/Session.php");

//TODO: check usage when this file is included from js.php files that include the main/top session.php

class Translator implements IRequestProcessor
{

    //$defines->set("TRANSLATOR_ENABLED", FALSE);
    //$defines->set("DB_ENABLED", FALSE);
    //
    //$defines->set("DEFAULT_LANGUAGE", "english");
    //$defines->set("DEFAULT_LANGUAGE_ISO3", "eng");

    /**
     * All captured texts for translation during translatePhrase()
     * @var SiteTextsBean
     */
    protected $phrases;

    /**
     * Translated content of the captured phrases
     * @var TranslationPhrasesBean
     */
    protected $translated_phrases;

    /**
     * Translated bean content
     * @var BeanTranslationsBean
     */
    protected $translated_beans;

    /**
     * Available languages
     * @var LanguagesBean
     */
    protected $languages;

    /**
     * The currently active language - Primary key value from LanguagesBean
     * @var int
     */
    protected $langID = -1;

    /**
     * The currently active language - 'language' column value from LanguagesBean
     * @var string
     */
    protected $language = array();

    public function __construct()
    {

        $this->phrases = new SiteTextsBean();
        $this->languages = new LanguagesBean();

        $this->translated_phrases = new TranslationPhrasesBean();
        $this->translated_beans = new BeanTranslationsBean();

        //prefer cookie langID
        $langID = -1;

        if (Session::Contains("langID")) {
            $langID = (int)Session::Get("langID");
        }
        if (Session::HaveCookie("langID")) {
            $langID = (int)Session::GetCookie("langID");
        }

        //no session or cookie set
        if ($langID > 0) {
            debug("Using Session/Cookies langID: ".$langID);
            try {
                $this->loadLanguageID($langID);
            }
            catch (Exception $e) {
                debug("Session/Cookies contain unavailable language ID - Setting DEFAULT_LANGUAGE language");
                $this->loadDefaultLanguage();

            }
        }
        else {
            $this->loadDefaultLanguage();
        }

    }

    protected function storeLanguage()
    {
        debug("Storing langID: ".$this->langID. " in session and cookies");
        Session::SetCookie("langID", $this->langID);
        Session::Set("langID", $this->langID);
    }

    public function processInput()
    {
        if (isset($_GET["change_language"])) {
            debug("Processing change_language request");

            $language = "";
            $langID = -1;

            if (isset($_GET["language"])) {
                $language = $_GET["language"];
            }
            if (isset($_GET["langID"])) {
                $langID = (int)$_GET["langID"];
            }

            $this->changeLanguage($language, $langID);

            $url = new URLBuilder();
            $url->buildFrom(currentURL());
            $url->remove("change_language");
            $url->remove("langID");
            $url->remove("language");
            debug("Redirecting to: ".$url->url());

            header("Location: ".$url->url());
            exit;
        }

    }

    protected function changeLanguage(string $language, int $langID)
    {
        $qry = $this->languages->queryFull();
        $qry->select->where()->add("langID", $langID, "=", "OR");
        $qry->select->where()->add("language", "'$language'", "=", "OR");
        if ($qry->exec() && $data = $qry->next()) {
            $this->language = $data;
            $this->langID = $data[$this->languages->key()];
            $this->storeLanguage();
        }
        else {
            Session::SetAlert("Request language not found");
        }
    }

    protected function loadLanguageID(int $langID)
    {
        $this->language = $this->languages->getByID($langID);
        $this->langID = $langID;
    }

    protected function loadDefaultLanguage()
    {
        debug("Loading default language");
        if (defined("DEFAULT_LANGUAGE")) {
            //query default language from define
            $qry = $this->languages->queryField("language", DEFAULT_LANGUAGE, 1);
            if ($qry->exec() && $data = $qry->next()) {
                $this->language = $data;
                $this->langID = $data[$this->languages->key()];
            }
            else {
                throw new Exception("DEFAULT_LANGUAGE is set in config but the LanguagesBean does not contain this language");
            }
        }
        else {
            //query the first language
            $qry = $this->languages->queryFull();
            $qry->select->limit = 1;
            $qry->select->order_by = $this->languages->key();

            if ($qry->exec() && $data = $qry->next()) {
                $this->language = $data;
                $this->langID = $data[$this->languages->key()];
            }
            else {
                throw new Exception("TRANSLATOR_ENABLED is set in config but LanguagesBean is empty");
            }
        }

        $this->storeLanguage();

    }


    public function translateBean(int $id, string $field_name, array &$data, string $tableName)
    {

        $qry = $this->translated_beans->query();
        $qry->select->fields()->set("translated");
        $where = $qry->select->where();
        $where->add("langID", $this->langID);
        $where->add("field_name", "'$field_name'");
        $where->add("table_name", "'$tableName'");
        $where->Add("bean_id", $id);

        $qry->select->limit = " 1 ";

        if ($qry->exec() && $result = $qry->next()) {
            $data[$field_name] = $result["translated"];
        }

    }

    public function translatePhrase(string $phrase): string
    {

        if (strlen(trim($phrase)) == 0) return $phrase;

        $phrase_hash = md5($phrase);

        $qry = $this->translated_phrases->queryLanguageID($this->langID);
        $qry->select->where()->add("hash_value", "'$phrase_hash'");
        $qry->select->limit = 1;

        $num = $qry->exec();

        if ($data = $qry->next()) {

            if ($data["trID"]>0) {
                return $data["translation"];
            }

            //not translated yet
            return $phrase;
        }
        else {
            //Capture new phrase. Insert into SiteTextsBean
            $phrase_data = array("value"=>$phrase, "hash_value"=>$phrase_hash);
            try {
                $this->phrases->insert($phrase_data);
            }
            catch (Exception $ex) {
                debug("Unable to capture new phrase: ". $ex->getMessage());
            }
        }

        return $phrase;
    }

    public function translateNumber($val)
    {
        $language = Session::Get("language");
        if (strcmp($language, "arabic") == 0) {
            $arnum = array("0" => "٠", "1" => "١", "2" => "٢", "3" => "٣", "4" => "٤", "5" => "٥", "6" => "٦",
                           "7" => "٧", "8" => "٨", "9" => "٩", "." => ".", "," => ",");
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


    /**
     * Return true if request data has loaded into this processor
     * @return bool
     */
    public function isProcessed(): bool
    {
        // TODO: Implement isProcessed() method.
        return false;
    }
}

$translator = new Translator();

$translator->processInput();

function trbean(int $id, string $field_name, array &$row, string $tableName)
{
    global $translator;
    $translator->translateBean($id, $field_name, $row, $tableName);
}

/**
 * @param string $str_original
 * @return string translated version of $str_original
 */
function tr(string $phrase): string
{

    global $translator;
    return $translator->translatePhrase($phrase);

}

function trnum($val)
{
    global $translator;
    return $translator->translateNumber($val);
}

?>
