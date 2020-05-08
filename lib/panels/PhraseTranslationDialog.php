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

        $btn = StyledButton::DefaultButton();
        $btn->setButtonType(StyledButton::TYPE_BUTTON);
        $btn->setText("Translate");
        $btn->setAttribute("action", "Translate");

        $this->appendButton($btn);

        $btn = StyledButton::DefaultButton();
        $btn->setButtonType(StyledButton::TYPE_BUTTON);
        $btn->setText("Close");
        $btn->setAttribute("action", "Close");
        $this->appendButton($btn);

        $h_translate = new TranslatePhraseAjaxHandler(HTMLPage::Instance());
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

    public function renderImpl()
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