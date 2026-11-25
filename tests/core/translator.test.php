<?php
/**
 * Translator: the configured default language (Config::DEFAULT_LANGUAGE_ISO3), the phrases from one map per
 * request, a new phrase captured once, the language cookie only when it changes, bean field translations
 */
include_once("utils/Translator.php");

Spark::Set(Config::DEFAULT_LANGUAGE, "bulgarian");
Spark::Set(Config::DEFAULT_LANGUAGE_ISO3, "bgn");

//the tables (the beans create them) and the data: english first, bulgarian the configured one
new LanguagesBean();
new SiteTextsBean();
new TranslationPhrasesBean();
new BeanTranslationsBean();
$pdo = TestPDO();
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
foreach (array("translation_phrases", "translation_beans", "site_texts", "languages") as $table) $pdo->exec("DELETE FROM $table");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
$pdo->exec("INSERT INTO languages (langID, language, lang_code) VALUES (1, 'english', 'eng'), (2, 'bulgarian', 'bgn')");
$hello = $pdo->prepare("INSERT INTO site_texts (textID, value, hash_value) VALUES (1, 'Hello', ?)");
$hello->execute(array(Spark::Hash("Hello")));
$pdo->exec("INSERT INTO site_texts (textID, value, hash_value) VALUES (2, 'Untranslated', '" . Spark::Hash("Untranslated") . "')");
$pdo->exec("INSERT INTO translation_phrases (langID, textID, translated) VALUES (2, 1, 'Здравей'), (1, 1, 'Hi')");
$pdo->exec("INSERT INTO translation_beans (table_name, field_name, bean_id, translated, langID) VALUES ('menu_items', 'menu_title', 7, 'Начало', 2)");


//enabled with its data in place - language.php starts the translator when it is included (as at boot)
Spark::Set(Config::TRANSLATOR_ENABLED, true);
Session::ClearCookie(Translator::KEY_LANGUAGE_ID);
include_once("utils/language.php");

test("the default language is the configured one, not the first", function () {
    assertTrue(Translator::Instance()->isEnabled(), "translator enabled");
    assertSame(2, Translator::Instance()->activeID());
    assertSame("bgn", Translator::Instance()->activeCode());
    assertSame("2", Session::GetCookie(Translator::KEY_LANGUAGE_ID), "the language cookie");
});

test("phrases: translated, untranslated and new from one map", function () use ($pdo) {
    assertSame("Здравей", tr("Hello"));
    assertSame("Untranslated", tr("Untranslated"));
    assertSame("Brand new", tr("Brand new"));
    assertSame("Brand new", tr("Brand new"));
    assertSame(1, (int)$pdo->query("SELECT COUNT(*) FROM site_texts WHERE value = 'Brand new'")->fetchColumn(), "captured once");
    assertSame(3, (int)$pdo->query("SELECT COUNT(*) FROM site_texts")->fetchColumn());
});

test("bean fields: the translation of the row in the active language, others unchanged", function () {
    $row = array("menu_title" => "Home");
    trbean(7, "menu_title", $row, "menu_items");
    assertSame("Начало", $row["menu_title"]);
    $other = array("menu_title" => "Books");
    trbean(8, "menu_title", $other, "menu_items");
    assertSame("Books", $other["menu_title"]);
});
