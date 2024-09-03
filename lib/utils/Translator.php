<?php
include_once("beans/LanguagesBean.php");
include_once("beans/SiteTextsBean.php");

include_once("beans/BeanTranslationsBean.php");
include_once("beans/TranslationPhrasesBean.php");

include_once("utils/IRequestProcessor.php");
include_once("utils/URLBuilder.php");
include_once("utils/Session.php");

class Translator implements IRequestProcessor, IGETConsumer
{

    /**
     * All captured texts for translation during translatePhrase()
     * @var SiteTextsBean
     */
    protected SiteTextsBean $phrases;

    /**
     * Translated content of the captured phrases
     * @var TranslationPhrasesBean
     */
    protected TranslationPhrasesBean $translated_phrases;

    /**
     * Translated bean content
     * @var BeanTranslationsBean
     */
    protected BeanTranslationsBean $translated_beans;

    /**
     * Available languages
     * @var LanguagesBean
     */
    protected LanguagesBean $languages;

    /**
     * The currently active language - Primary key value from LanguagesBean
     * @var int
     */
    protected int $langID = 1;

    /**
     * The currently active language - 'language' column value from LanguagesBean
     * @var string
     */
    protected array $language = array();

    const KEY_LANGUAGE_ID = "langID";
    const KEY_CHANGE_LANGUAGE = "change_language";
    const KEY_LANGUAGE = "language";

    public function __construct()
    {

        $this->phrases = new SiteTextsBean();
        $this->languages = new LanguagesBean();

        $this->translated_phrases = new TranslationPhrasesBean();
        $this->translated_beans = new BeanTranslationsBean();

        //prefer cookie langID
        $langID = -1;

        if (Session::Contains(Translator::KEY_LANGUAGE_ID)) {
            $langID = (int)Session::Get(Translator::KEY_LANGUAGE_ID);
            debug("Session ".Translator::KEY_LANGUAGE_ID.": ".$langID);
        }
        else if (Session::HaveCookie(Translator::KEY_LANGUAGE_ID)) {
            $langID = (int)Session::GetCookie(Translator::KEY_LANGUAGE_ID);
            debug("Cookies ".Translator::KEY_LANGUAGE_ID.": ".$langID);
        }

        //no session or cookie set
        if ($langID > 0) {
            debug("Using Session/Cookies ".Translator::KEY_LANGUAGE_ID.": ".$langID);
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

    protected function loadLanguageID(int $langID) : void
    {
        $this->language = $this->languages->getByID($langID);
        $this->langID = $langID;
        $this->storeLanguage();
    }

    public function getParameterNames(): array
    {
        return array(Translator::KEY_LANGUAGE_ID,
            Translator::KEY_LANGUAGE,
            Translator::KEY_CHANGE_LANGUAGE);
    }

    protected function storeLanguage() : void
    {
        debug("Storing langID: ".$this->langID. " in session and cookies");
        Session::SetCookie("langID", $this->langID);
        Session::Set("langID", $this->langID);
    }

    public function processInput()
    {
        if (isset($_GET[Translator::KEY_CHANGE_LANGUAGE])) {
            debug("Processing change_language request");

            $language = "";
            $langID = -1;

            if (isset($_GET[Translator::KEY_LANGUAGE])) {
                $language = sanitizeInput($_GET[Translator::KEY_LANGUAGE]);
            }
            if (isset($_GET[Translator::KEY_LANGUAGE_ID])) {
                $langID = (int)$_GET[Translator::KEY_LANGUAGE_ID];
            }

            try {
                $this->changeLanguage($language, $langID);
            }
            catch (Exception $e) {
                debug("Error: ".$e->getMessage());
            }

            $url = new URLBuilder();
            $url->buildFrom(currentURL());
            $url->remove(Translator::KEY_CHANGE_LANGUAGE);
            $url->remove(Translator::KEY_LANGUAGE_ID);
            $url->remove(Translator::KEY_LANGUAGE);
            debug("Redirecting to: ".$url->url());

            header("Location: ".$url->url());
            exit;
        }

    }

    protected function changeLanguage(string $language, int $langID) : void
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


    protected function loadDefaultLanguage() : void
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
        try {
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
        catch (Exception $e) {
            debug("Unable to translate: ".$e->getMessage());
        }

    }

    public function translatePhrase(string $phrase): string
    {
        $result = $phrase;

        if (strlen(trim($phrase)) == 0) return $result;

        try {
            $phrase_hash = md5($phrase);

            $qry = $this->translated_phrases->queryLanguageID($this->langID);
            $qry->select->where()->add("hash_value", "'$phrase_hash'");
            $qry->select->limit = 1;

            if ($qry->exec() && $data = $qry->next()) {
                if ($data["trID"]>0) {
                    $result = $data["translation"];
                }
            }
            else {
                //Capture new phrase. Insert into SiteTextsBean
                $phrase_data = array("value"=>DBConnections::Get()->escape($phrase), "hash_value"=>$phrase_hash);
                try {
                    $this->phrases->insert($phrase_data);
                }
                catch (Exception $ex) {
                    throw new Exception("Unable to capture new phrase: ". $ex->getMessage());
                }
            }
        }
        catch (Exception $e) {
            debug("Error: ".$e->getMessage());
        }

        return $result;
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
?>
