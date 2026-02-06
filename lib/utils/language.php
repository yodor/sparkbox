<?php
include_once("utils/Translator.php");

$translator = null;

if (defined("TRANSLATOR_ENABLED") &&
    !defined("SKIP_DB") &&
    !defined("SKIP_TRANSLATOR") &&
    !defined("STORAGE_REQUEST")) {
    try {
        $translator = new Translator();
        $translator->processInput();
    }
    catch (Exception $e) {
        $translator = null;
        Debug::ErrorLog("Translator can not be enabled: ".$e->getMessage());
    }
}

function trbean(int $id, string $field_name, array &$row, string $tableName)
{
    global $translator;
    if ($translator instanceof Translator) {
        $translator->translateBean($id, $field_name, $row, $tableName);
    }
}

/**
 * @param string $phrase
 * @return string translated version of $str_original
 */
function tr(string $phrase): string
{
    global $translator;
    if ($translator instanceof Translator) {
        return $translator->translatePhrase($phrase);
    }
    return $phrase;

}

function trnum($val)
{
    global $translator;
    if ($translator instanceof Translator) {
        return $translator->translateNumber($val);
    }
    return $val;
}
?>
