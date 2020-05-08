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

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "sparkfront/css/BeanTranslationDialog.css";
        $arr[] = SITE_ROOT . "sparkfront/css/MCETextArea.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "sparkfront/js/popups/BeanTranslationDialog.js";
        $arr[] = SITE_ROOT . "sparkfront/js/MCETextArea.js";
        $arr[] = SITE_ROOT . "sparkfront/js/tiny_mce/jquery.tinymce.min.js";
        return $arr;
    }

    public function renderImpl()
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
        $renderer->list_key = "langID";
        $renderer->list_label = "language";


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
