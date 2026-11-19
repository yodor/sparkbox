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
     * The phrases of the active language, loaded once per request at the first tr(): hash => the translation,
     * null when the phrase has none (captured, not translated)
     * @var array|null
     */
    protected ?array $phraseMap = null;

    /**
     * The bean translations of the active language, per "table.field", loaded at the first trbean() of each:
     * bean id => the translation
     * @var array
     */
    protected array $beanMaps = array();

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
        //no active language when the translator is disabled
        return (string)($this->language["lang_code"] ?? "");
    }

    /**
     * Active language code according to ISO2
     * @return string
     */
    public function activeCodeISO2() : string
    {
        $code = $this->activeCode();
        if ($code !== "") return substr($code, 0, 2);
        //translator disabled - language of the configured locale
        return (string)Locale::getPrimaryLanguage((string)(Spark::Get(Config::DEFAULT_LOCALE) ?? "en_US"));
    }

    /**
     * Active language full name
     * @return string
     */
    public function activeName() : string
    {
        return (string)($this->language["language"] ?? "");
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
        $this->languages = new LanguagesBean();

        $this->translated_phrases = new TranslationPhrasesBean();

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

    /**
     * The active language in the cookie - only when it changes (no Set-Cookie on every page)
     * @return void
     */
    protected function storeLanguage() : void
    {
        if (Session::GetCookie(Translator::KEY_LANGUAGE_ID) === (string)$this->langID) return;
        Debug::ErrorLog("Storing langID: " . $this->langID . " in cookies");
        Session::SetCookie(Translator::KEY_LANGUAGE_ID, (string)$this->langID);
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

            //back to the address of the page (not the script of the module)
            $url = URL::Requested();

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

    /**
     * The language of the site config (Config::DEFAULT_LANGUAGE_ISO3 - its lang_code), else the first language
     * @return void
     * @throws Exception
     */
    protected function loadDefaultLanguage() : void
    {
        $code = (string)Spark::Get(Config::DEFAULT_LANGUAGE_ISO3);
        Debug::ErrorLog("Loading the default language: $code");

        $data = null;
        if ($code !== "") $data = $this->firstLanguage("lang_code", $code);
        if (is_null($data)) $data = $this->firstLanguage();
        if (is_null($data)) throw new Exception("TRANSLATOR_ENABLED is set in config but LanguagesBean is empty");

        $this->language = $data;
        $this->langID = (int)$data[$this->languages->key()];
        $this->storeLanguage();
    }

    /**
     * The first language (by its ID) - matching '$column' = '$value' when given; null for none
     * @throws Exception
     */
    protected function firstLanguage(string $column = "", string $value = "") : ?array
    {
        $query = $this->languages->queryFull();
        if ($column !== "") $query->stmt->where()->match($column, $value);
        $query->stmt->order($this->languages->key(), OrderDirection::ASC);
        $query->stmt->limit(1);
        $query->exec();
        $data = $query->next();
        $query->free();
        return $data;
    }


    /**
     * The translation of the field '$field_name' of the row '$id' of '$tableName' into '$data' (when there is one) -
     * the translations of the table field load with one query, at its first use in the request
     * @param int $id
     * @param string $field_name
     * @param array $data
     * @param string $tableName
     * @return void
     */
    public function translateBean(int $id, string $field_name, array &$data, string $tableName) : void
    {
        if ($id < 1 || $field_name === "" || $tableName === "") return;

        $mapKey = $tableName . "." . $field_name;
        try {
            if (!isset($this->beanMaps[$mapKey])) $this->beanMaps[$mapKey] = $this->loadBeanMap($tableName, $field_name);
        }
        catch (Exception $e) {
            Debug::ErrorLog("Unable to load the translations of $mapKey: " . $e->getMessage());
            $this->beanMaps[$mapKey] = array();
        }

        if (isset($this->beanMaps[$mapKey][$id])) $data[$field_name] = $this->beanMaps[$mapKey][$id];
    }

    /**
     * bean id => translation of the field of the table in the active language
     * @throws Exception
     */
    protected function loadBeanMap(string $tableName, string $field_name) : array
    {
        $map = array();
        if (is_null($this->translated_beans)) $this->translated_beans = new BeanTranslationsBean();
        $qry = $this->translated_beans->query("bean_id", "translated");
        $where = $qry->stmt->where();
        $where->match("langID", $this->langID);
        $where->match("table_name", $tableName);
        $where->match("field_name", $field_name);
        $qry->exec();
        while ($row = $qry->next()) {
            $map[(int)$row["bean_id"]] = (string)$row["translated"];
        }
        $qry->free();
        return $map;
    }

    /**
     * The translation of '$phrase' in the active language - the phrase itself when it has none. A phrase seen for
     * the first time is captured (site_texts) for the translators of the admin.
     * @param string $phrase
     * @return string
     */
    public function translatePhrase(string $phrase): string
    {
        if (trim($phrase) === "") return $phrase;

        try {
            if (is_null($this->phraseMap)) $this->loadPhrases();

            $hash = Spark::Hash($phrase);
            if (!array_key_exists($hash, $this->phraseMap)) {
                //once per request, also when the capture fails
                $this->phraseMap[$hash] = null;
                $this->capturePhrase($phrase, $hash);
                return $phrase;
            }
            $translation = $this->phraseMap[$hash];
            return is_null($translation) ? $phrase : $translation;
        }
        catch (Exception $e) {
            Debug::ErrorLog("Error: " . $e->getMessage());
        }
        return $phrase;
    }

    /**
     * All phrases with their translation in the active language - one query
     * @throws Exception
     */
    protected function loadPhrases() : void
    {
        $this->phraseMap = array();
        $qry = $this->translated_phrases->queryPhrase($this->langID);
        $qry->exec();
        while ($row = $qry->next()) {
            $this->phraseMap[(string)$row["hash"]] = ((int)$row["trID"] > 0) ? (string)$row["translation"] : null;
        }
        $qry->free();
        Debug::ErrorLog("Translator: " . count($this->phraseMap) . " phrases loaded for langID: " . $this->langID);
    }

    protected function capturePhrase(string $phrase, string $phrase_hash) : void
    {
        try {
            if (is_null($this->phrases)) $this->phrases = new SiteTextsBean();
            $trID = $this->phrases->insert(array("value" => $phrase, "hash_value" => $phrase_hash));
            Debug::ErrorLog("Captured new phrase with textID: $trID");
        }
        catch (Exception $ex) {
            //captured by a parallel request meanwhile (the hash is unique) - nothing lost
            Debug::ErrorLog("Phrase not captured [$phrase_hash]: " . $ex->getMessage());
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