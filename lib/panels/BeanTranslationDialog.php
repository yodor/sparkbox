<?php
include_once("panels/MessageDialog.php");
include_once("handlers/TranslateBeanAjaxHandler.php");

//IFinalRenderer delegate rendering to page control does not need to call render
class BeanTranslationDialog extends MessageDialog implements IPageComponent
{

    public function __construct()
    {
        parent::__construct("Translate", "bean_translator");
        $this->show_close_button = TRUE;

        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        RequestController::addAjaxHandler(new TranslateBeanAjaxHandler());
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/BeanTranslationDialog.css";
        $arr[] = SPARK_LOCAL . "/css/MCETextArea.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/popups/BeanTranslationDialog.js";
        $arr[] = SPARK_LOCAL . "/js/MCETextArea.js";
        $arr[] = SPARK_LOCAL . "/js/tiny_mce/jquery.tinymce.min.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_translate = new ColorButton();
        $btn_translate->setContents("Translate");
        $btn_translate->setAttribute("action", "Translate");
        $this->buttonsBar->append($btn_translate);

        $btn_clear = new ColorButton();
        $btn_clear->setContents("Clear");
        $btn_clear->setAttribute("action", "Clear");
        $this->buttonsBar->append($btn_clear);

        $btn_close = new ColorButton();
        $btn_close->setContents("Close");
        $btn_close->setAttribute("action", "Close");
        $this->buttonsBar->append($btn_close);
    }

    protected function renderImpl()
    {

        echo tr("Original Text") . ":<BR>";

        echo "<textarea class='original_text' name='original_text' rows=10 readonly=true>";
        echo "</textarea>";
        echo "<br>";

        echo "<div class='AjaxProgress'></div>";

        include_once("input/DataInputFactory.php");

        $ls = new DataInput("langID", "Translation Language", 1);

        $renderer = new SelectField($ls);

        include_once("beans/LanguagesBean.php");
        $lb = new LanguagesBean();

        $lb->select()->where = " langID>1 ";

        $renderer->setIterator($lb->query());
        $renderer->getItemRenderer()->setValueKey("langID");
        $renderer->getItemRenderer()->setLabelKey("language");

        include_once("components/InputComponent.php");
        $cmp = new InputComponent($ls);
        $cmp->render();

        echo "<form>";

        echo "<textarea name='translation' rows=10 >";

        echo "</textarea>";

        echo "</form>";
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var bean_translator = new BeanTranslationDialog();
                bean_translator.attachWith("bean_translator");

                $("BODY").find("[action='TranslateBeanField']").each(function (index) {

                    var is_mce = $(this).parent().children(".MCETextArea").length > 0;
                    console.log("is_mce=" + is_mce);


                    $(this).click(function (event) {

                        bean_translator.show($(this).attr("field"), is_mce);


                    });
                });

            });
        </script>
        <?php

    }

}

?>
