<?php
include_once("lib/panels/MessageDialog.php");
include_once("lib/handlers/TranslatePhraseAjaxHandler.php");

class PhraseTranslationDialog extends MessageDialog implements IHeadRenderer
{

	public function __construct()
	{
	    parent::__construct("Phrase Translator", "phrase_translator");

	    
	    $this->show_close_button=TRUE;
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

	    $h_translate = new TranslatePhraseAjaxHandler(SitePage::getInstance());
	    RequestController::addAjaxHandler($h_translate);
		
	}
	public function renderScript()
	{
	    echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/popups/PhraseTranslationDialog.js'></script>";
	    echo "\n";
	}
	public function renderStyle()
	{
	    echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/PhraseTranslationDialog.css' type='text/css'>";
	    echo "\n";
	}
	public function renderImpl()
	{
	    

	      echo tr("Original Text").":<br>";

	      echo "<textarea READONLY class='original_text' rows=5>";
	      echo "</textarea>";

	      echo "<br>";

	      echo "<form>";

		echo tr("Translation").":";

		echo "<div class='AjaxProgress'></div>";

		echo "<textarea name='translation' rows=5 >";
		echo "</textarea>";


	      echo "</form>";


	}

}
?>