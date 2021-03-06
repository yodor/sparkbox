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

        $lb->select()->where()->add("langID", 1 ,">");
        $qry = $lb->query();
        $qry->select->fields()->set("langID", "language");
        $renderer->setIterator($qry);
        $renderer->getItemRenderer()->setValueKey("langID");
        $renderer->getItemRenderer()->setLabelKey("language");

        $this->input = $ls;


        include_once("components/InputComponent.php");
        $cmp = new InputComponent($this->input);
        $this->buttonsBar->prepend($cmp);

        $responder = new TranslateBeanResponder();
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/BeanTranslationDialog.css";
        $arr[] = SPARK_LOCAL . "/css/MCETextArea.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/BeanTranslationDialog.js";
        $arr[] = SPARK_LOCAL . "/js/MCETextArea.js";
        $arr[] = SPARK_LOCAL . "/js/tiny_mce/jquery.tinymce.min.js";
        return $arr;
    }

    protected function initButtons()
    {

        $container = new Container();
        $container->addClassName("ButtonGroup");

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
                let bean_translator = new BeanTranslationDialog();
                bean_translator.initialize();

                $("BODY").find("[action='TranslateBeanField']").each(function (index) {

                    var is_mce = $(this).parent().children(".MCETextArea").length > 0;
                    console.log("is_mce=" + is_mce);

                    $(this).click(function (event) {
                        let editID = $(".BeanFormEditor").first().attr("editID");
                        if (editID<1) {
                            showAlert("<?php echo tr("Please submit the form to enable translation");?>");
                            return;
                        }
                        bean_translator.show($(this).attr("field"), is_mce);

                    });
                });

            });
        </script>
        <?php

    }

}

?>