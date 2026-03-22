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

    /**
     * If TRANSLATOR_ENABLED we get initialized
     * @return void
     * @throws Exception
     */
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

        if ($langID > 0) {
            try {
                $this->loadLanguageID($langID);
            } catch (Exception $e) {
                Debug::ErrorLog("Cookies reference unavailable language ID");
                $this->loadDefaultLanguage();
            }
        }
        else {
            Debug::ErrorLog("Cookies does not have language ID yet");
            $this->loadDefaultLanguage();
        }

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
                $language = substr($_GET[Translator::KEY_LANGUAGE],0, 32);
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
            Debug::ErrorLog("Language change redirect: ".$url);

            header("Location: ".$url);
            exit;
        }

    }

    protected function loadLanguageID(int $langID) : void
    {
        Debug::ErrorLog("Loading ".Translator::KEY_LANGUAGE_ID.": ".$langID);
        $this->language = $this->languages->getByID($langID, "lang_code", "language");
        $this->langID = $langID;
        $this->storeLanguage();
    }

    protected function changeLanguage(string $language, int $langID) : void
    {
        $query = $this->languages->queryFull();

        $doQuery = false;
        if ($langID>0) {
            $query->stmt->where()->match("langID", $langID);
            $doQuery = true;
        }
        if (strlen(trim($language))>0) {
            $query->stmt->where()->match("language", $language);
            $doQuery = true;
        }

        if ($doQuery) {

            if ($query->count() == 0) {
                Debug::ErrorLog("Requested language is not found");
                $this->loadDefaultLanguage();
                return;
            }

            $query->exec();
            if ($data = $query->next()) {
                $this->language = $data;
                $this->langID = (int)$data[$this->languages->key()];
            }
            $query->free();

            $this->storeLanguage();
        }
        else {
            $this->loadDefaultLanguage();
        }

    }

    protected function loadDefaultLanguage() : void
    {
        Debug::ErrorLog("Loading default configured language");

        if (defined("DEFAULT_LANGUAGE")) {
            //query default language from define
            $query = $this->languages->queryField("language", DEFAULT_LANGUAGE, 1, "lang_code");
            if ($query->count() == 0) throw new Exception("Configured DEFAULT_LANGUAGE is not available in LanguagesBean");
        }
        else {
            //query the first language
            $query = $this->languages->queryFull();
            $query->stmt->limit(1);
            $query->stmt->order($this->languages->key(), OrderDirection::ASC);
            if ($query->count() == 0) throw new Exception("TRANSLATOR_ENABLED is set in config but LanguagesBean is empty");
        }

        $query->exec();
        if ($data = $query->next()) {
            $this->language = $data;
            $this->langID = $data[$this->languages->key()];
        }
        $query->free();

        $this->storeLanguage();

    }


    public function translateBean(int $id, string $field_name, array &$data, string $tableName) : void
    {

        try {
            if ($id<1 || empty($field_name) || empty($tableName)) throw new Exception("ID, field_name and table name required parameters");

            $qry = $this->translated_beans->query("translated");

            $where = $qry->stmt->where();
            $where->match("langID", $this->langID);
            $where->match("field_name", $field_name);
            $where->match("table_name", $tableName);
            $where->match("bean_id", $id);

            $qry->stmt->limit(1);

            $qry->exec();
            if ($result = $qry->next()) {
                $data[$field_name] = $result["translated"];
            }
            $qry->free();
        }
        catch (Exception $e) {
            Debug::ErrorLog("Unable to translate: ".$e->getMessage());
        }

    }

    public function translatePhrase(string $phrase): string
    {

        if (strlen(trim($phrase)) == 0) return $phrase;

        $translated = $phrase;

        try {

            $phrase_hash = Spark::Hash($phrase);

            $qry = $this->translated_phrases->queryPhrase($this->langID);
            $qry->stmt->where()->match("hash_value", $phrase_hash);
            $qry->stmt->limit(1);

            if ($qry->count() == 0) {
                $this->capturePhrase($phrase, $phrase_hash);
                return $phrase;
            }
            $qry->exec();

            if ($data = $qry->nextResult()) {
                //translation is done for $this->langID ?
                if ((int)$data->get("trID")>0) {
                    //return translated version
                    $translated = $data->get("translation");
                }
            }
            $qry->free();

        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: ".$e->getMessage());
        }

        return $translated;
    }

    protected function capturePhrase(string $phrase, string $phrase_hash) : void
    {
        try {
            $phrase_data = array("value"=>$phrase, "hash_value"=>$phrase_hash);
            $trID = $this->phrases->insert($phrase_data);
            Debug::ErrorLog("Captured new phrase with trID: $trID");
        }
        catch (Exception $ex) {
            throw new Exception("Failed capturing new phrase [$phrase_hash]: ". $ex->getMessage());
        }
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