<?php
include_once("dialogs/MessageDialog.php");
include_once("responders/json/TranslatePhraseResponder.php");

class PhraseTranslatorInit extends PageScript
{
    public function code() : string
    {
        return <<<JS

            const phrase_translator = new PhraseTranslationDialog();
            phrase_translator.initialize();
JS;
    }

}

class PhraseTranslationDialog extends MessageDialog
{

    public function __construct()
    {
        parent::__construct();

        $this->setTitle("Phrase Translator");
        $this->setType(MessageDialog::TYPE_PLAIN);

        $this->content->items()->clear();

        $phraseInput = DataInputFactory::Create(DataInputFactory::TEXTAREA, "phrase", "Original Text", 0);
        $phraseInput->getRenderer()->input()->setAttribute("rows", 5);
        $phrase = new InputComponent($phraseInput);
        $this->content->items()->append($phrase);

        $translationInput = DataInputFactory::Create(DataInputFactory::TEXTAREA, "translation", "Translation", 0);
        $translationInput->getRenderer()->input()->setAttribute("rows", 5);
        $translation = new InputComponent($translationInput);
        $this->content->items()->append($translation);

        new TranslatePhraseResponder();

        //init the dialog - ready for edit call
        new PhraseTranslatorInit();

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/PhraseTranslationDialog.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/PhraseTranslationDialog.js";
        return $arr;
    }

    protected function initButtons() : void
    {
        $btn = new Button();
        $btn->setContents("Cancel");
        $btn->setAttribute("action", "Close");
        $this->buttonsBar->items()->append($btn);

        $btn = new Button();
        $btn->setContents("Translate");
        $btn->setAttribute("action", "Translate");
        $btn->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn);
    }



}

?>
