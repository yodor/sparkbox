<?php
include_once("lib/panels/MessageDialog.php");
include_once("lib/handlers/TranslateBeanAjaxHandler.php");


//IFinalRenderer delegate rendering to page control does not need to call render
class BeanTranslationDialog extends MessageDialog implements IFinalRenderer, IHeadRenderer
{

	public function __construct()
	{
		parent::__construct("Translate", "bean_translator");
		$this->show_close_button=TRUE;
		
		$this->setDialogType(MessageDialog::TYPE_PLAIN);
		
		$btn_translate = StyledButton::DefaultButton();
		$btn_translate->setText("Translate");
		$btn_translate->setAttribute("action", "Translate");
		$this->appendButton($btn_translate);
		
		$btn_clear = StyledButton::DefaultButton();
		$btn_clear->setText("Clear");
		$btn_clear->setAttribute("action", "Clear");
		$this->appendButton($btn_clear);
		
		$btn_close = StyledButton::DefaultButton();
		$btn_close->setText("Close");
		$btn_close->setAttribute("action", "Close");
		$this->appendButton($btn_close);

		RequestController::addAjaxHandler(new TranslateBeanAjaxHandler());
	}
	public function renderScript()
	{

	    echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/popups/BeanTranslationDialog.js'></script>";
	    echo "\n";
	}
	public function renderStyle()
	{	

	    echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/BeanTranslationDialog.css' type='text/css'>";
	    echo "\n";
	}
	public function renderImpl()
	{

		
		echo tr("Original Text").":<BR>";
		
		echo "<textarea class='original_text' rows=10 >";
		echo "</textarea>";
		echo "<br>";

		
		echo "<div class='AjaxProgress'></div>";

		include_once("lib/input/InputFactory.php");
		
		$ls = new InputField("langID", "Translation Language", 1);
		
		$renderer = new SelectField();

		include_once("lib/beans/LanguagesBean.php");
		$lb = new LanguagesBean();
		

		$lb->setFilter(" langID>1 ");

		$renderer->setSource($lb);
		$renderer->list_key="langID";
		$renderer->list_label="language";


		$ls->setRenderer($renderer);

		include_once("lib/components/InputComponent.php");
		$cmp = new InputComponent();
		$cmp->setField($ls);
		$cmp->render();

		echo "<form>";
		
		echo "<textarea name=translation rows=10 >";

		echo "</textarea>";

		echo "</form>";
?>
<script type='text/javascript'>
addLoadEvent(function(){
  var bean_translator = new BeanTranslationDialog();
  bean_translator.attachWith("bean_translator");

  $("BODY").find("[action='TranslateBeanField']").each(function(index){

     $(this).click(function(event){

	  bean_translator.show($(this).attr("field"));

     });
  });

});
</script>
<?php

	}


}
?>