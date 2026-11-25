<?php
include_once("beans/LanguagesBean.php");
include_once("beans/BeanTranslationsBean.php");

include_once("utils/IRequestProcessor.php");
include_once("utils/url/URL.php");
include_once("utils/Session.php");

/**
 * The language of a page: the texts of the code and the translations of the content.
 *
 *  - The code is English. Its texts (tr()) come from the language packs of the active language:
 *    '<layer>/lang/<code>.php' returning array(English text => translation) - core lib/lang, store lib/store/lang,
 *    plugins, the site class/lang - merged in that order (the site overrides the store, the store the core). A text
 *    without a translation is shown in English. Nothing is read from or written to the database for them.
 *  - The content (the rows of the beans) is entered in the language of the site; its translations into other
 *    languages are rows of translation_beans (trbean(), the admin 'Translate' dialog) - read only with
 *    Config::TRANSLATOR_ENABLED.
 *
 * The language of the site: the primary language of Config::DEFAULT_LOCALE ('bg-bg' - 'bg'). With
 * Config::TRANSLATOR_ENABLED a visitor may choose another language of the 'languages' table
 * (?change_language=1&langID=<id>) - kept in a cookie set only then.
 * Language codes are two-letter (ISO 639-1: en, bg, el); older three-letter codes of the table are understood.
 */
class Translator implements IRequestProcessor, IGETConsumer
{
    const string KEY_LANGUAGE_ID = "langID";
    const string KEY_CHANGE_LANGUAGE = "change_language";
    const string KEY_LANGUAGE = "language";

    const string PACK_FOLDER = "lang";

    /**
     * Three-letter codes of earlier 'languages' rows and their two-letter codes
     */
    const array LEGACY_CODES = array("bgr" => "bg", "bgn" => "bg", "bul" => "bg", "eng" => "en", "ell" => "el",
        "gre" => "el", "deu" => "de", "ger" => "de", "fra" => "fr", "fre" => "fr", "rus" => "ru", "tur" => "tr",
        "ron" => "ro", "rum" => "ro", "srp" => "sr", "mkd" => "mk", "mac" => "mk", "ita" => "it", "spa" => "es");

    protected static ?Translator $instance = null;

    public static function Instance() : Translator
    {
        if (is_null(Translator::$instance)) {
            Translator::$instance = new Translator();
        }
        return Translator::$instance;
    }

    /**
     * The two-letter code of the active language
     */
    protected string $code = "en";

    /**
     * The 'languages' row of the active language (TRANSLATOR_ENABLED) - loaded when needed
     */
    protected ?array $language = null;

    /**
     * The phrases of the active language (the merged packs) - loaded at the first tr()
     * @var array<string, string>|null
     */
    protected ?array $phrases = null;

    /**
     * The bean translations of the active language, per "table.field", loaded at the first trbean() of each:
     * bean id => translation
     * @var array<string, array<int, string>>
     */
    protected array $beanMaps = array();

    protected ?BeanTranslationsBean $translated_beans = null;

    private function __construct()
    {
        $this->code = Translator::LocaleLanguage((string)Spark::Get(Config::DEFAULT_LOCALE));
        if (!Spark::GetBoolean(Config::TRANSLATOR_ENABLED)) return;

        try {
            $this->processInput();
            //a language the visitor chose
            if (Session::HaveCookie(Translator::KEY_LANGUAGE_ID)) {
                $row = $this->languageRow("langID", (int)Session::GetCookie(Translator::KEY_LANGUAGE_ID));
                if (is_array($row)) $this->useLanguage($row);
            }
        }
        catch (Exception $e) {
            Debug::ErrorLog("Translator: " . $e->getMessage());
        }
    }

    /**
     * The two-letter code of a locale ('bg-bg', 'bg_BG' - 'bg'), "en" for none
     */
    public static function LocaleLanguage(string $locale) : string
    {
        $code = strtolower(substr(trim($locale), 0, 2));
        return preg_match("/^[a-z]{2}$/", $code) ? $code : "en";
    }

    /**
     * The two-letter code of a 'languages' code ('bg', or an earlier three-letter one: 'bgr', 'eng')
     */
    public static function CodeOf(string $langCode) : string
    {
        $langCode = strtolower(trim($langCode));
        if (strlen($langCode) === 2) return $langCode;
        return Translator::LEGACY_CODES[$langCode] ?? substr($langCode, 0, 2);
    }

    /**
     * The texts are translated (the language packs) - always; kept for the callers of the earlier translator
     */
    public function isEnabled() : bool
    {
        return true;
    }

    /**
     * The two-letter code of the active language (the 'lang' of the page)
     */
    public function activeCodeISO2() : string
    {
        return $this->code;
    }

    public function activeCode() : string
    {
        return $this->code;
    }

    /**
     * The 'languages' ID of the active language - -1 without the table (TRANSLATOR_ENABLED off, no row)
     */
    public function activeID() : int
    {
        $row = $this->activeLanguage();
        return is_array($row) ? (int)$row["langID"] : -1;
    }

    public function activeName() : string
    {
        $row = $this->activeLanguage();
        return is_array($row) ? (string)$row["language"] : $this->code;
    }

    /**
     * The 'languages' row of the active language - read once, only with TRANSLATOR_ENABLED
     */
    protected function activeLanguage() : ?array
    {
        if (!is_null($this->language)) return $this->language;
        if (!Spark::GetBoolean(Config::TRANSLATOR_ENABLED)) return null;
        try {
            $languages = new LanguagesBean();
            $query = $languages->queryFull();
            $query->exec();
            while ($row = $query->next()) {
                if (Translator::CodeOf((string)$row["lang_code"]) === $this->code) {
                    $this->language = $row;
                    break;
                }
            }
            $query->free();
        }
        catch (Exception $e) {
            Debug::ErrorLog("Translator: " . $e->getMessage());
        }
        return $this->language;
    }

    protected function languageRow(string $column, int|string $value) : ?array
    {
        $languages = new LanguagesBean();
        $query = $languages->queryFull();
        $query->stmt->where()->match($column, $value);
        $query->stmt->limit(1);
        $query->exec();
        $row = $query->next();
        $query->free();
        return is_array($row) ? $row : null;
    }

    protected function useLanguage(array $row) : void
    {
        $this->language = $row;
        $this->code = Translator::CodeOf((string)$row["lang_code"]);
        $this->phrases = null;
        $this->beanMaps = array();
    }

    public function getParameterNames(): array
    {
        return array(Translator::KEY_LANGUAGE_ID, Translator::KEY_LANGUAGE, Translator::KEY_CHANGE_LANGUAGE);
    }

    /**
     * ?change_language=1&langID=<id> (or &language=<name>): the visitor's language in a cookie, back to the page
     */
    public function processInput(): void
    {
        if (!isset($_GET[Translator::KEY_CHANGE_LANGUAGE])) return;

        try {
            $row = null;
            if (isset($_GET[Translator::KEY_LANGUAGE_ID])) $row = $this->languageRow("langID", (int)$_GET[Translator::KEY_LANGUAGE_ID]);
            else if (isset($_GET[Translator::KEY_LANGUAGE])) $row = $this->languageRow("language", substr((string)$_GET[Translator::KEY_LANGUAGE], 0, 32));
            if (is_array($row)) {
                Session::SetCookie(Translator::KEY_LANGUAGE_ID, (string)$row["langID"]);
            }
        }
        catch (Exception $e) {
            Debug::ErrorLog("Translator: " . $e->getMessage());
        }

        $url = URL::Requested();
        $url->remove(Translator::KEY_CHANGE_LANGUAGE);
        $url->remove(Translator::KEY_LANGUAGE_ID);
        $url->remove(Translator::KEY_LANGUAGE);
        header("Location: " . $url);
        exit;
    }

    public function isProcessed(): bool
    {
        return false;
    }

    /**
     * The phrases of the active language: the packs of the layers merged (the later layer wins)
     * @return array<string, string>
     */
    protected function phrases() : array
    {
        if (!is_null($this->phrases)) return $this->phrases;
        $this->phrases = array();
        foreach (SparkLoader::Factory(Translator::PACK_FOLDER)->findAll($this->code) as $file) {
            $pack = include($file);
            if (is_array($pack)) $this->phrases = array_replace($this->phrases, $pack);
        }
        return $this->phrases;
    }

    /**
     * The translation of '$phrase' in the active language - the phrase itself when its pack has none
     */
    public function translatePhrase(string $phrase): string
    {
        if ($phrase === "") return $phrase;
        return $this->phrases()[$phrase] ?? $phrase;
    }

    /**
     * The translation of the field '$field_name' of the row '$id' of '$tableName' into '$data' (when there is one) -
     * the translations of the table field load with one query, at its first use in the request (TRANSLATOR_ENABLED)
     */
    public function translateBean(int $id, string $field_name, array &$data, string $tableName) : void
    {
        if ($id < 1 || $field_name === "" || $tableName === "") return;
        if (!Spark::GetBoolean(Config::TRANSLATOR_ENABLED)) return;

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
        $langID = $this->activeID();
        if ($langID < 1) return $map;
        if (is_null($this->translated_beans)) $this->translated_beans = new BeanTranslationsBean();
        $qry = $this->translated_beans->query("bean_id", "translated");
        $where = $qry->stmt->where();
        $where->match("langID", $langID);
        $where->match("table_name", $tableName);
        $where->match("field_name", $field_name);
        $qry->exec();
        while ($row = $qry->next()) {
            $map[(int)$row["bean_id"]] = (string)$row["translated"];
        }
        $qry->free();
        return $map;
    }
}
