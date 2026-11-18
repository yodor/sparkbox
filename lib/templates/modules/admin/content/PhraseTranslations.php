<?php
include_once("templates/content/BeanList.php");
include_once("beans/TranslationPhrasesBean.php");
include_once("dialogs/json/PhraseTranslationDialog.php");
include_once("components/InlineScript.php");

class PhraseTranslatorInit extends InlineScript implements IPageComponent
{
    protected function finalize() : void
    {
        $code = <<<JS
document.phraseTranslator = new PhraseTranslationDialog();
document.phraseTranslator.initialize();
JS;
        $this->setCode($code);
        parent::finalize();
    }
}

/**
 * Translations of the site texts for the language from Template::Condition() (LanguagesBean).
 * Translate / Clear open the phrase translation dialog (TranslatePhraseResponder).
 */
class PhraseTranslations extends BeanList
{
    protected int $langID = -1;

    public function setup(TemplateConfig $config): void
    {
        $condition = Template::Condition();
        if (is_null($condition)) throw new Exception("Language condition not set");
        $this->langID = (int)$condition->getID();

        $config->iterator = (new TranslationPhrasesBean())->queryPhrase($this->langID);
        $config->listFields = array("phrase" => "Phrase", "translation" => "Translation");
        $config->searchField = array("st.value");
        parent::setup($config);

        //keep the language in the search requests
        $form = $this->getSearch()->getForm();
        $form->addInput(DataInputFactory::Create(InputType::HIDDEN, "langID", "langID", false));
        $form->getInput("langID")->setValue($this->langID);
        $form->getInput("langID")->skip_search_filter_processing = true;

        $dialog = new PhraseTranslationDialog();
        $dialog->setAttribute("langID", $this->langID);
        new PhraseTranslatorInit();
    }

    public function fillPageActions(ActionCollection $collection): void
    {
    }

    protected function initItemActions(ActionCollection $act): void
    {
        $act->append(new Action("Translate", "javascript:document.phraseTranslator.edit(%textID%)"));
        $act->append(Action::PipeSeparator());
        $act->append(new Action("Clear", "javascript:document.phraseTranslator.clear(%textID%)"));
    }

    public function initialize(): void
    {
        parent::initialize();
        foreach (array("phrase", "translation") as $column) {
            $cell = $this->tableView()->getColumn($column)->getCellRenderer();
            $cell->setAttribute("relation", $column);
            $cell->addDataAttribute("textID");
            $cell->addDataAttribute("trID");
        }
    }

    protected function getContentTitle(): string
    {
        return tr("Translations");
    }
}
