<?php
include_once("utils/Translator.php");

$translator = new Translator();
$translator->processInput();

function trbean(int $id, string $field_name, array &$row, string $tableName)
{
    global $translator;
    $translator->translateBean($id, $field_name, $row, $tableName);
}

/**
 * @param string $str_original
 * @return string translated version of $str_original
 */
function tr(string $phrase): string
{

    global $translator;
    return $translator->translatePhrase($phrase);

}

function trnum($val)
{
    global $translator;
    return $translator->translateNumber($val);
}

?>
