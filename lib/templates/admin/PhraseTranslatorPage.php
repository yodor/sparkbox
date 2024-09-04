<?php
include_once("templates/admin/BeanListPage.php");

include_once("beans/LanguagesBean.php");
include_once("beans/TranslationPhrasesBean.php");

include_once("components/KeywordSearch.php");
include_once("dialogs/PhraseTranslationDialog.php");

include_once("iterators/SQLQuery.php");

class PhraseTranslatorPage extends BeanListPage
{
    protected $langID = -1;

    public function __construct()
    {
        parent::__construct();

        $rc = new BeanKeyCondition(new LanguagesBean(), "../list.php", array("language"));
        $this->setRequestCondition($rc);

        $langID = $rc->getID();

        $this->page->setName(tr("Translations For") . ": " . $rc->getData("language"));

        $dialog = new PhraseTranslationDialog();
        $dialog->setAttribute("langID", $langID);

        $this->langID = $langID;

        $bean = new TranslationPhrasesBean();

        $this->setIterator($bean->queryLanguageID($this->langID));

        $search_fields = array("st.value");
        $this->getSearch()->getForm()->setFields($search_fields);

        $this->setListFields(array("phrase" => "Phrase", "translation" => "Translation"));
    }

    protected function initPageActions()
    {
        //
    }

    protected function initViewActions(ActionCollection $act)
    {
        $action_translate = new Action("Translate", "javascript:phrase_translator.edit(%textID%)");
        $act->append($action_translate);
        $act->append(Action::PipeSeparator());

        $action_clear = new Action("Clear", "javascript:phrase_translator.clear(%textID%)");
        $act->append($action_clear);
    }

    public function initView()
    {

        parent::initView();

        $phrase = $this->view->getColumn("phrase")->getCellRenderer();
        $phrase->setAttribute("relation", "phrase");
        $phrase->addValueAttribute("textID");
        $phrase->addValueAttribute("trID");

        $translation = $this->view->getColumn("translation")->getCellRenderer();
        $translation->setAttribute("relation", "translation");
        $translation->addValueAttribute("textID");
        $translation->addValueAttribute("trID");

    }

    protected function renderImpl()
    {
        parent::renderImpl();


    }
}
