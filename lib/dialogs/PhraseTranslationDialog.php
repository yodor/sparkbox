<?php
include_once("dialogs/MessageDialog.php");
include_once("handlers/TranslatePhraseAjaxHandler.php");

class PhraseTranslationDialog extends MessageDialog
{

    protected $type = MessageDialog::TYPE_PLAIN;

    public function __construct()
    {
        parent::__construct("Phrase Translator", "phrase_translator");

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

        echo "<table class='Items'>";

        echo "<tr><td>";
        echo "<label>" . tr("Original Text") . ": </label>";
        echo "</td></tr>";

        echo "<tr><td class='cell phrase'>";
        echo "<textarea READONLY name='phrase' rows='5' >";
        echo "</textarea>";
        echo "</td></tr>";

        echo "<tr><td>";
        echo "<label>" . tr("Translation") . ": </label>";
        echo "</td></tr>";

        echo "<tr><td class='cell translation'>";
        echo "<textarea name='translation' rows='5' >";
        echo "</textarea>";
        echo "</td></tr>"; //item

        echo "</table>";

    }

}

?>