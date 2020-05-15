<?php
include_once("panels/MessageDialog.php");
include_once("handlers/TranslatePhraseAjaxHandler.php");

class PhraseTranslationDialog extends MessageDialog
{

    public function __construct()
    {
        parent::__construct("Phrase Translator", "phrase_translator");

        $this->show_close_button = TRUE;
        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $h_translate = new TranslatePhraseAjaxHandler();
        RequestController::addAjaxHandler($h_translate);

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/PhraseTranslationDialog.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/popups/PhraseTranslationDialog.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn = new ColorButton();
        $btn->setContents("Translate");
        $btn->setAttribute("action", "Translate");

        $this->buttonsBar->append($btn);

        $btn = new ColorButton();
        $btn->setContents("Close");
        $btn->setAttribute("action", "Close");
        $this->buttonsBar->append($btn);
    }

    protected function renderImpl()
    {

        echo tr("Original Text") . ":<br>";

        echo "<textarea READONLY class='original_text' rows=5>";
        echo "</textarea>";

        echo "<br>";

        echo "<form>";

        echo tr("Translation") . ":";

        echo "<div class='AjaxProgress'></div>";

        echo "<div class='InputField TextArea'>";
        echo "<textarea name='translation' rows=5>";
        echo "</textarea>";
        echo "</div>";

        echo "</form>";

    }

}

?>