<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("lib/components/renderers/cells/CallbackTableCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/beans/LanguagesBean.php");
include_once("lib/panels/PhraseTranslationDialog.php");
include_once("lib/iterators/SQLResultIterator.php");


$page = new AdminPage();
$page->checkAccess(ROLE_CONFIG_MENU);

$langID = -1;
if (!isset($_GET["langID"])) {
    throw new Exception("langID not passed");
}
$langID = (int)$_GET["langID"];

$lb = new LanguagesBean();
$lrow = $lb->getByID($langID);


$bean = new SiteTextsBean();

$menu = array();

$dialog = new PhraseTranslationDialog();
$dialog->setAttribute("langID", $langID);


$search_fields = array("value");

$scomp = new KeywordSearchComponent($search_fields);

$scomp->form_prepend = "<input type=hidden name=langID value='$langID'>";

// SELECT st.textID, st.value as phrase, t.translated as translation, coalesce(t.trID,-1) as trID, coalesce(t.langID,2) as langID FROM  WHERE 1 having langID=2

// $qry = $bean->getSelectQuery();
$qry = new SelectQuery();
$qry->fields = " st.textID, st.value as phrase, t.translated as translation, coalesce(t.trID,-1) as trID, coalesce(t.langID,$langID) as langID  ";
$qry->from = " site_texts st LEFT JOIN translation_phrases t ON st.textID=t.textID ";
$qry->having = " langID=$langID ";

$search_qry = $scomp->getForm()->searchFilterQuery();

$qry = $qry->combineWith($search_qry);


$view = new TableView(new SQLResultIterator($qry, $bean->key()));
$view->setCaption("Available Phrases For Translation");
// $view->setClassName("TranslationPhrases");
// $view->setAttribute("langID", $langID);


$view->items_per_page = 20;

$view->addColumn(new TableColumn($bean->key(), "ID"));
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
$action_translate = new Action("Translate", "javascript:phrase_translator.edit(%textID%)", array(new ActionParameter("textID", "textID")));
$act->addAction($action_translate);
$act->addAction(new PipeSeparatorAction());

$action_clear = new Action("Clear", "javascript:phrase_translator.clear(%textID%)", array(new ActionParameter("textID", "textID")));
$act->addAction($action_clear);


$view->getColumn("actions")->setCellRenderer($act);


$page->startRender($menu);


echo "<div class='page_caption'>";
echo tr("Translations For") . ": " . tr($lrow["language"]);
echo "</div>";


$scomp->render();

$view->render();


?>

    <script type='text/javascript'>

        var phrase_translator = new PhraseTranslationDialog();
        phrase_translator.attachWith("phrase_translator");

    </script>
<?php

$page->finishRender();

?>