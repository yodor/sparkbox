<?php
include_once("utils/Translator.php");

$translator = Translator::Instance();

function trbean(int $id, string $field_name, array &$row, string $tableName) : void
{
    if ($id<0 || empty($field_name)||empty($tableName)) return;

    $translator = Translator::Instance();
    if ($translator->isEnabled()) {
        $translator->translateBean($id, $field_name, $row, $tableName);
    }
}

/**
 * @param string $phrase
 * @return string translated version of $str_original
 */
function tr(string $phrase): string
{
    $translator = Translator::Instance();
    if ($translator->isEnabled()) {
        return $translator->translatePhrase($phrase);
    }
    return $phrase;
}