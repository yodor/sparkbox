<?php
/**
 * Translator: the texts of the code from the language packs of the layers (core lib/lang, the site class/lang -
 * the site wins), English without a translation, values in a translated format, no database and no cookie for
 * them; the language of the site from its locale; old three-letter codes; the content translations
 * (translation_beans) with TRANSLATOR_ENABLED
 */
include_once("utils/Translator.php");
include_once("beans/BeanTranslationsBean.php");

//the layers of the loader as at boot: the core, the site (the test application)
SparkLoader::AddLocation(realpath(TESTS_PATH . "/../lib"), "");
SparkLoader::AddLocation(APP_PATH, "class");

Spark::Set(Config::DEFAULT_LOCALE, "bg-bg");
Spark::Set(Config::TRANSLATOR_ENABLED, true);

//the languages (english, bulgarian - two-letter codes) and a content translation
new LanguagesBean();
new BeanTranslationsBean();
$pdo = TestPDO();
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
foreach (array("translation_beans", "languages") as $table) $pdo->exec("DELETE FROM $table");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
$pdo->exec("INSERT INTO languages (langID, language, lang_code) VALUES (1, 'english', 'en'), (2, 'bulgarian', 'bg')");
$pdo->exec("INSERT INTO translation_beans (table_name, field_name, bean_id, translated, langID) VALUES ('menu_items', 'menu_title', 7, 'Начало', 2)");

Session::ClearCookie(Translator::KEY_LANGUAGE_ID);
include_once("utils/language.php");

test("the language of the site from its locale; no cookie for it", function () {
    assertSame("bg", Translator::Instance()->activeCodeISO2());
    assertSame("", Session::GetCookie(Translator::KEY_LANGUAGE_ID), "no language cookie");
    assertSame(2, Translator::Instance()->activeID(), "the 'languages' row of the code");
});

test("texts: the packs of the layers, the site overrides the core, English without a translation, values", function () {
    assertSame("Изтриване", tr("Delete"), "the site pack overrides the core");
    assertSame("Изчисти", tr("Clear"), "the core pack");
    assertSame("Текст на сайта", tr("A text of the site"));
    assertSame("Not translated text", tr("Not translated text"));
    assertSame("Офиси: 12", tr("Offices: %s", 12));
    assertSame("Untranslated 5", tr("Untranslated %s", 5));
    assertSame("100%", tr("100%"), "no format without values");
});

test("codes: two-letter from a locale or an old three-letter code", function () {
    assertSame("bg", Translator::LocaleLanguage("bg-bg"));
    assertSame("en", Translator::LocaleLanguage(""));
    foreach (array("bgr" => "bg", "bgn" => "bg", "bul" => "bg", "eng" => "en", "ell" => "el", "gre" => "el", "bg" => "bg") as $old => $code) {
        assertSame($code, Translator::CodeOf($old), $old);
    }
});

test("content: the translation of a bean field in the active language, others unchanged", function () {
    $row = array("menu_title" => "Home");
    trbean(7, "menu_title", $row, "menu_items");
    assertSame("Начало", $row["menu_title"]);
    $other = array("menu_title" => "Books");
    trbean(8, "menu_title", $other, "menu_items");
    assertSame("Books", $other["menu_title"]);
});

test("a new languages table: English (the code) and the language of the site, two-letter codes", function () use ($pdo) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DROP TABLE languages");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    DBTableBean::ForgetColumnTypes("languages");
    new LanguagesBean();
    $rows = $pdo->query("SELECT lang_code, language FROM languages ORDER BY langID")->fetchAll(PDO::FETCH_NUM);
    assertSame(array(array("en", "english"), array("bg", "bulgarian")), $rows);
});
