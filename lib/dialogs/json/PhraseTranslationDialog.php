<?php
include_once("dialogs/json/JSONDialog.php");
include_once("responders/json/TranslatePhraseResponder.php");



class PhraseTranslationDialog extends JSONDialog
{

    public function __construct()
    {
        parent::__construct();

        $this->setTitle("Phrase Translator");
        $this->setType(MessageDialog::TYPE_PLAIN);

        $this->content->items()->clear();

        $phraseInput = DataInputFactory::Create(InputType::TEXTAREA, "phrase", "Original Text", 0);
        $phraseInput->getRenderer()->input()->setAttribute("rows", 5);
        $phrase = new InputComponent($phraseInput);
        $this->content->items()->append($phrase);

        $translationInput = DataInputFactory::Create(InputType::TEXTAREA, "translation", "Translation", 0);
        $translationInput->getRenderer()->input()->setAttribute("rows", 5);
        $translation = new InputComponent($translationInput);
        $this->content->items()->append($translation);

        $this->setResponder(new TranslatePhraseResponder());

        //init the dialog - ready for edit call


    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/PhraseTranslationDialog.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/dialogs/json/PhraseTranslationDialog.js";
        return $arr;
    }

    protected function initButtons() : void
    {
        $btn = new Button();
        $btn->setContents(tr("Cancel"));
        $btn->setAttribute("action", "Close");
        $this->buttonsBar->items()->append($btn);

        $btn = new Button();
        $btn->setContents(tr("Translate"));
        $btn->setAttribute("action", "Translate");
        $btn->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn);
    }



}

?>
