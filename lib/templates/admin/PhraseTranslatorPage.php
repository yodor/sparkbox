<?php
include_once("templates/admin/BeanListPage.php");

include_once("beans/LanguagesBean.php");
include_once("beans/SiteTextsBean.php");

include_once("components/KeywordSearch.php");
include_once("dialogs/PhraseTranslationDialog.php");

include_once("iterators/SQLQuery.php");

class PhraseTranslatorPage extends BeanListPage
{
    protected $langID = -1;

    public function __construct()
    {
        parent::__construct();

        $rc = new RequestBeanKey(new LanguagesBean(), "../list.php", array("language"));

        $langID = $rc->getID();

        $this->page->setName(tr("Translations For") . ": " . $rc->getData("language"));

        $dialog = new PhraseTranslationDialog();
        $dialog->setAttribute("langID", $langID);

        $this->langID = $langID;

        $search_fields = array("value");
        //default is POST for this form
        $scomp = new KeywordSearch($search_fields);

        $bean = new SiteTextsBean();
        $qry = $bean->query();

        $sel = $qry->select;
        $sel->fields = " st.textID, st.value as phrase, t.translated as translation, coalesce(t.trID,-1) as trID, coalesce(t.langID,$langID) as langID  ";
        $sel->from = " site_texts st LEFT JOIN translation_phrases t ON st.textID=t.textID ";
        $sel->having = " langID=$langID ";

        $search_qry = $scomp->getForm()->searchFilterSelect();

        $sel->combine($search_qry);

        $this->append($scomp);

        $this->query = $qry;
    }

    protected function initPageActions()
    {
        //
    }

    protected function initViewActions(ActionCollection $act)
    {
        $action_translate = new Action("Translate", "javascript:phrase_translator.edit(%textID%)");
        $act->append($action_translate);
        $act->append(new PipeSeparator());

        $action_clear = new Action("Clear", "javascript:phrase_translator.clear(%textID%)");
        $act->append($action_clear);
    }

    public function initView()
    {


        $view = new TableView($this->query);
        $view->setCaption("Available Phrases For Translation");
        // $view->setClassName("TranslationPhrases");
        // $view->setAttribute("langID", $langID);

        $view->items_per_page = 20;

        $view->addColumn(new TableColumn($this->query->key(), "ID"));
        $view->addColumn(new TableColumn("phrase", "Phrase"));
        $view->getColumn("phrase")->getCellRenderer()->setAttribute("relation", "phrase");

        $view->addColumn(new TableColumn("translation", "Translation"));
        $view->getColumn("translation")->getCellRenderer()->setAttribute("relation", "translation");

        $view->getColumn("phrase")->getCellRenderer()->addValueAttribute("textID");
        $view->getColumn("phrase")->getCellRenderer()->addValueAttribute("trID");

        $view->getColumn("translation")->getCellRenderer()->addValueAttribute("textID");
        $view->getColumn("translation")->getCellRenderer()->addValueAttribute("trID");

        $view->addColumn(new TableColumn("actions", "Actions"));

        //command actions edit/delete
        $act = new ActionsTableCellRenderer();

        $this->view_actions = $act->getActions();

        $this->initViewActions($this->view_actions);

        $view->getColumn("actions")->setCellRenderer($act);

        $this->view = $view;

        $this->append($this->view);

    }

    protected function renderImpl()
    {
        parent::renderImpl();

        ?>
        <script type='text/javascript'>

            var phrase_translator = new PhraseTranslationDialog();
            phrase_translator.attachWith("phrase_translator");

        </script>
        <?php
    }
}