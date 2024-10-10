<?php
include_once("dialogs/json/JSONDialog.php");
include_once("responders/json/TranslateBeanResponder.php");
include_once("components/PageScript.php");

class BeanTranslatorInit extends PageScript
{
    function code() : string
    {
        $alertText = tr("Please submit the form to enable translation");

        return <<<JS
        const beanDialog = new BeanTranslationDialog();
        beanDialog.initialize();

        document.querySelectorAll("[action='TranslateBeanField']").forEach((element) => {
            
            element.addEventListener("click", (event)=>{
                let editID = document.querySelector(".BeanFormEditor").getAttribute("editID");
                if (editID<1) {
                    showAlert("{$alertText}");
                    return;
                }
                beanDialog.show(element.getAttribute("field"), false);
            });
            
        });
JS;

    }
}
//IFinalRenderer delegate rendering to page control does not need to call render
class BeanTranslationDialog extends JSONDialog implements IPageComponent
{

    protected DataInput $input;

    public function __construct()
    {
        parent::__construct();

        $this->setTitle("Translate");
        $this->setType(MessageDialog::TYPE_PLAIN);

        $this->content->items()->clear();

        $phraseInput = DataInputFactory::Create(DataInputFactory::TEXTAREA, "original_text", "Original Text", 0);
        $phraseInput->getRenderer()->input()->setAttribute("rows", 5);
        $phraseInput->getRenderer()->input()->setAttribute("readonly");

        $phrase = new InputComponent($phraseInput);
        $this->content->items()->append($phrase);

        $translationInput = DataInputFactory::Create(DataInputFactory::TEXTAREA, "translation", "Translation", 0);
        $translationInput->getRenderer()->input()->setAttribute("rows", 5);
        $translation = new InputComponent($translationInput);
        $this->content->items()->append($translation);


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
        $this->buttonsBar->items()->prepend($cmp);

        $this->setResponder(new TranslateBeanResponder());

        new BeanTranslatorInit();
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/BeanTranslationDialog.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/BeanTranslationDialog.js";
        return $arr;
    }

    protected function initButtons() : void
    {

        $btn_translate = new Button();
        $btn_translate->setContents(tr("Translate"));
        $btn_translate->setAttribute("action", "Translate");
        $this->buttonsBar->items()->append($btn_translate);

        $btn_clear = new Button();
        $btn_clear->setContents(tr("Clear"));
        $btn_clear->setAttribute("action", "Clear");
        $this->buttonsBar->items()->append($btn_clear);

        $btn_close = new Button();
        $btn_close->setContents(tr("Close"));
        $btn_close->setAttribute("action", "Close");
        $this->buttonsBar->items()->append($btn_close);

    }


}

?>
