<?php
include_once("utils/Translator.php");

/**
 * The translation of the field '$field_name' of the row '$id' of the table '$tableName' into '$row' - the content
 * translations (translation_beans, Config::TRANSLATOR_ENABLED)
 */
function trbean(int $id, string $field_name, array &$row, string $tableName) : void
{
    if ($id < 0 || empty($field_name) || empty($tableName)) return;
    Translator::Instance()->translateBean($id, $field_name, $row, $tableName);
}

/**
 * The text '$phrase' (English, the key of the language packs) in the active language. With '$values' the
 * translation is a format: tr("Offices: %s", $count) - the values are not part of the key.
 * @param string $phrase
 * @param string|int|float ...$values
 * @return string
 */
function tr(string $phrase, string|int|float ...$values): string
{
    $text = Translator::Instance()->translatePhrase($phrase);
    return (count($values) > 0) ? vsprintf($text, $values) : $text;
}
