<?php
include_once("beans/LanguagesBean.php");
include_once("beans/SiteTextsBean.php");

include_once("beans/BeanTranslationsBean.php");
include_once("beans/TranslationPhrasesBean.php");

include_once("utils/IRequestProcessor.php");
include_once("utils/url/URL.php");
include_once("utils/Session.php");

class Translator implements IRequestProcessor, IGETConsumer
{

    protected bool $enabled = false;

    protected static ?Translator $instance = null;


    public static function Instance() : Translator
    {
        if (self::$instance == null) {
            self::$instance = new Translator();
        }
        return self::$instance;
    }
    /**
     * All captured texts for translation during translatePhrase()
     * @var SiteTextsBean|null
     */
    protected ?SiteTextsBean $phrases = null;

    /**
     * Translated content of the captured phrases
     * @var TranslationPhrasesBean|null
     */
    protected ?TranslationPhrasesBean $translated_phrases = null;

    /**
     * Translated bean content
     * @var BeanTranslationsBean|null
     */
    protected ?BeanTranslationsBean $translated_beans = null;

    /**
     * Available languages
     * @var LanguagesBean|null
     */
    protected ?LanguagesBean $languages = null;

    /**
     * The currently active language - Primary key value from LanguagesBean
     * @var int
     */
    protected int $langID = 1;

    /**
     * The currently active language result row from LanguagesBean
     * @var array
     */
    protected array $language = array();

    const string KEY_LANGUAGE_ID = "langID";
    const string KEY_CHANGE_LANGUAGE = "change_language";
    const string KEY_LANGUAGE = "language";

    /**
     * Language ID primary key from LanguagesBean
     * @return int
     */
    public function activeID() : int
    {
        return $this->langID;
    }

    /**
     * Active language code according to ISO3
     * @return string
     */
    public function activeCode() : string
    {
        return $this->language["lang_code"];
    }

    /**
     * Active language code according to ISO2
     * @return string
     */
    public function activeCodeISO2() : string
    {
        return substr($this->language["lang_code"], 0,2);
    }

    /**
     * Active language full name
     * @return string
     */
    public function activeName() : string
    {
        return $this->language["language"];
    }

    private function __construct()
    {
        if (!Spark::GetBoolean(Config::TRANSLATOR_ENABLED)) {
            Debug::ErrorLog("Translator not enabled in config");
            $this->setEnabled(false);
            return;
        }

        try {
            $this->initialize();
            $this->processInput();
            $this->setEnabled(true);
        }
        catch (Exception $e) {
            $this->setEnabled(false);
            Debug::ErrorLog("Translator can not be enabled: ".$e->getMessage());
        }
    }

    public function setEnabled(bool $enabled) : void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    public function initialize() : void
    {
        Debug::ErrorLog("Initializing translator...");
        $this->phrases = new SiteTextsBean();
        $this->languages = new LanguagesBean();

        $this->translated_phrases = new TranslationPhrasesBean();
        $this->translated_beans = new BeanTranslationsBean();

        //prefer cookie langID
        $langID = -1;

        if (Session::HaveCookie(Translator::KEY_LANGUAGE_ID)) {
            $langID = (int)Session::GetCookie(Translator::KEY_LANGUAGE_ID);
            Debug::ErrorLog(Translator::KEY_LANGUAGE_ID.": ".$langID);
        }


        //no session or cookie set
        if ($langID > 0) {

            try {
                $this->loadLanguageID($langID);
            }
            catch (Exception $e) {
                Debug::ErrorLog("Session/Cookies contain unavailable language ID - Setting DEFAULT_LANGUAGE language");
                $this->loadDefaultLanguage();
            }
        }
        else {
            $this->loadDefaultLanguage();
        }

    }

    protected function loadLanguageID(int $langID) : void
    {
        Debug::ErrorLog("Loading ".Translator::KEY_LANGUAGE_ID.": ".$langID);
        $this->language = $this->languages->getByID($langID, "lang_code", "language");
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
        Debug::ErrorLog("Storing langID: ".$this->langID. " in cookies");
        Session::SetCookie("langID", $this->langID);
        //Session::Set("langID", $this->langID);
    }

    public function processInput(): void
    {
        if (isset($_GET[Translator::KEY_CHANGE_LANGUAGE])) {
            Debug::ErrorLog("Processing change_language request");

            $language = "";
            $langID = -1;

            if (isset($_GET[Translator::KEY_LANGUAGE])) {
                $language = Spark::SanitizeInput($_GET[Translator::KEY_LANGUAGE]);
            }
            if (isset($_GET[Translator::KEY_LANGUAGE_ID])) {
                $langID = (int)$_GET[Translator::KEY_LANGUAGE_ID];
            }

            try {
                $this->changeLanguage($language, $langID);
            }
            catch (Exception $e) {
                Debug::ErrorLog("Error: ".$e->getMessage());
            }

            $url = URL::Current();

            $url->remove(Translator::KEY_CHANGE_LANGUAGE);
            $url->remove(Translator::KEY_LANGUAGE_ID);
            $url->remove(Translator::KEY_LANGUAGE);
            Debug::ErrorLog("Redirecting to: ".$url);

            header("Location: ".$url);
            exit;
        }

    }

    protected function changeLanguage(string $language, int $langID) : void
    {
        $qry = $this->languages->queryFull();
        $qry->stmt->where()->add("langID", $langID);
        $qry->stmt->where()->add("language", $language);
        $qry->exec();
        if ($data = $qry->next()) {
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
        Debug::ErrorLog("Loading default language");
        if (defined("DEFAULT_LANGUAGE")) {
            //query default language from define
            $qry = $this->languages->queryField("language", DEFAULT_LANGUAGE, 1, "lang_code");
            $qry->exec();
            if ($data = $qry->next()) {
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
            $qry->stmt->limit(1);
            $qry->stmt->order($this->languages->key(), OrderDirection::ASC);
            $qry->exec();
            if ($data = $qry->next()) {
                $this->language = $data;
                $this->langID = $data[$this->languages->key()];
            }
            else {
                throw new Exception("TRANSLATOR_ENABLED is set in config but LanguagesBean is empty");
            }
        }

        $this->storeLanguage();

    }


    public function translateBean(int $id, string $field_name, array &$data, string $tableName) : void
    {


        try {
            if ($id<1 || empty($field_name) || empty($tableName)) throw new Exception("ID, field_name and table name required parameters");

            $qry = $this->translated_beans->query();
            $qry->stmt->set("translated");
            $where = $qry->stmt->where();
            $where->add("langID", $this->langID);
            $where->add("field_name", $field_name);
            $where->add("table_name", $tableName);
            $where->add("bean_id", $id);

            $qry->stmt->limit(1);
            $qry->exec();

            if ($result = $qry->next()) {
                $data[$field_name] = $result["translated"];
            }
        }
        catch (Exception $e) {
            Debug::ErrorLog("Unable to translate: ".$e->getMessage());
        }

    }

    public function translatePhrase(string $phrase): string
    {

        if (strlen(trim($phrase)) == 0) return $phrase;

        try {
            $phrase_hash = Spark::Hash($phrase);

            $qry = $this->translated_phrases->queryLanguageID($this->langID);
            $qry->stmt->where()->add("hash_value", $phrase_hash);
            $qry->stmt->limit(1);
            //$qry->stmt->setMeta("QueryPhrase");

            $qry->exec();

            $translated = "";

            if ($data = $qry->nextResult()) {
                //phrase is already captured - check if translation exists for the current langID
                $textID = (int)$data->get("textID");

                if ((int)$data->get("trID")>0) {
                    //return translated version
                    $translated = $data->get("translation");
                }
                else {
                    //translation not done yet
                    $translated = $phrase;
                }
            }
            $qry->free();
            if ($translated) return $translated;

            //Capture new phrase. Insert into SiteTextsBean
            try {
                $phrase_data = array("value"=>$phrase, "hash_value"=>$phrase_hash);
                $trID = $this->phrases->insert($phrase_data);
                Debug::ErrorLog("Captured new phrase with trID: $trID");
            }
            catch (Exception $ex) {
                throw new Exception("Failed capturing new phrase [$phrase_hash]: ". $ex->getMessage());
            }

        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage());
        }

        return $phrase;
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