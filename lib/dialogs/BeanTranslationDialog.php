<?php
include_once("dialogs/MessageDialog.php");
include_once("responders/json/TranslateBeanResponder.php");

//IFinalRenderer delegate rendering to page control does not need to call render
class BeanTranslationDialog extends MessageDialog implements IPageComponent
{

    protected $input;

    public function __construct()
    {
        parent::__construct("Translate", "bean_translator");
        //$this->show_close_button = TRUE;

        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        include_once("input/DataInputFactory.php");

        $ls = new DataInput("langID", "Language", 1);

        $renderer = new SelectField($ls);

        include_once("beans/LanguagesBean.php");
        $lb = new LanguagesBean();

        $lb->select()->where = " langID>1 ";

        $renderer->setIterator($lb->query());
        $renderer->getItemRenderer()->setValueKey("langID");
        $renderer->getItemRenderer()->setLabelKey("language");

        $this->input = $ls;


        include_once("components/InputComponent.php");
        $cmp = new InputComponent($this->input);
        $this->buttonsBar->prepend($cmp);

        $responder = new TranslateBeanResponder();
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

        $container = new Container();
        $container->setClassName("ButtonGroup");

        $btn_translate = new ColorButton();
        $btn_translate->setContents("Translate");
        $btn_translate->setAttribute("action", "Translate");
        $container->append($btn_translate);

        $btn_clear = new ColorButton();
        $btn_clear->setContents("Clear");
        $btn_clear->setAttribute("action", "Clear");
        $container->append($btn_clear);

        $btn_close = new ColorButton();
        $btn_close->setContents("Close");
        $btn_close->setAttribute("action", "Close");
        $container->append($btn_close);

        $this->buttonsBar->append($container);
    }

    protected function renderImpl()
    {

        echo "<table class='Items'>";

        echo "<tr><td>";
        echo "<label>" . tr("Original Text") . ": </label>";
        echo "</td></tr>";

        echo "<tr><td class='cell original_text InputField'>";
        echo "<textarea READONLY name='original_text' rows='5' ></textarea>";
        echo "</td></tr>";

        echo "<tr><td>";
        echo "<label>" . tr("Translation") . ": </label>";
        echo "</td></tr>";

        echo "<tr><td class='cell translation InputField'>";
        echo "<textarea name='translation' rows='5' >";
        echo "</textarea>";
        echo "</td></tr>"; //item

        echo "</table>";


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
