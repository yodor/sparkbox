class BeanTranslationDialog extends JSONDialog {

    constructor() {

        super();

        this.setID("bean_translator");

        // this.langID = -1;
        // this.field_name = "";
        // this.bean_class = "";
        // this.bean_id = "";

        this.language_alert = "Please select translation language";

        this.translator_editor = null;
        this.original_editor = null;

        this.req.setResponder("bean_translator");

    }

    buttonAction(action) {
        if (action=="Translate"){
            this.store();
        }
        else if (action == "Clear") {
            this.clear();
        }
        else if (action == "Close") {
            this.remove();
        }
    }

    changeLanguage() {

        this.langID = parseInt(this.modal_pane.popup.find("[name='langID']").val());

        var translation = this.modal_pane.popup.find("[name='translation']");

        translation.val("");

        if (this.langID < 1) return;

        this.req.setParameter("langID", this.langID);

        this.fetch();
    }

    show(field_name, is_mce) {

        this.is_mce = is_mce;

        super.show();

        let editor = $(".BeanFormEditor");
        //bean_id, field_name, bean_class
        var bean_id = editor.attr("editID");
        var bean_class = editor.attr("bean");

        console.log("beanID: " + bean_id + " bean_class: " + bean_class + " DataInput: " + field_name);

        this.req.setParameter("field_name", field_name);
        this.req.setParameter("beanID", bean_id);
        this.req.setParameter("bean_class", bean_class);
        this.req.setParameter("langID", -1);

        let instance = this;

        //set first from combo box choosing language
        this.modal_pane.popup.find("[name='langID']").val("");

        this.modal_pane.popup.find("[name='langID']").change(function () {
            this.changeLanguage();
        }.bind(this));


        var source_content = editor.find("FORM [name='" + field_name + "']").val();

        //console.log("Source Content: " + source_content);

        this.modal_pane.popup.find("[name='original_text']").val(source_content);
        this.modal_pane.popup.find("[name='translation']").val("");

        if (is_mce) {

            var mce = new MCETextArea();
            mce.setClass("TEXTAREA");
            mce.setName("original_text");
            mce.initialize();

            mce.onEditorInit = function (editor) {

                editor.setMode('readonly');
                instance.original_editor = editor;

            };

            this.modal_pane.popup.find(".cell.original_text").removeClass("InputField");

            var mce1 = new MCETextArea();
            mce1.setClass("TEXTAREA");
            mce1.setName("translation");
            mce1.initialize();

            mce1.onEditorInit = function (editor) {

                instance.translator_editor = editor;
                console.log("Translator init done: " + editor.id);

                editor.getBody().setAttribute('contenteditable', false);

                editor.setContent("");
            };

            this.modal_pane.popup.find(".cell.translation").removeClass("InputField");

        }

    }

    processResult(responder, funct, result) {
        let jsonResult = result.json_result;
        let message = jsonResult.message;

        if (funct == "store") {
            showAlert(message);
        }
        else if (funct == "fetch") {
            if (!jsonResult.translation) showAlert(message);
            if (this.translator_editor) this.translator_editor.getBody().setAttribute('contenteditable', true);
            this.modal_pane.popup.find("[name='translation']").val(jsonResult.translation);
        }
        else if (funct == "clear") {
            this.modal_pane.popup.find("[name='translation']").val("");
            if (this.translator_editor) this.translator_editor.setContent("");
            showAlert(message);
        }
    }

    store() {

        if (this.langID < 1) {
            showAlert(this.language_alert);
            return;
        }

        this.req.setFunction("store");

        let translation = this.modal_pane.popup.find("[name='translation']");
        this.req.setPostParameter("translation", translation.val());

        this.req.start();
    }



    fetch() {

        if (this.langID < 1) {
            showAlert(this.language_alert);
            return;
        }

        this.req.setFunction("fetch");
        this.req.clearPostParameters();
        this.req.start();

    }

    clear() {

        if (this.langID < 1) {
            showAlert(this.language_alert);
            return;
        }

        this.req.setFunction("clear");
        this.req.clearPostParameters();
        this.req.start();
    }


    remove() {
        var popup = this.modal_pane.popup;

        if (this.original_editor) {
            this.original_editor.remove();
            this.original_editor.destroy();
            this.original_editor = null;

            popup.find("[name='original_text']").data("mce_init_done", 0);
        }

        if (this.translator_editor) {
            this.translator_editor.remove();
            this.translator_editor.destroy();
            this.translator_editor = null;
            popup.find("[name='translation']").data("mce_init_done", 0);
        }

        popup.find("[name='langID']").val("");

        super.remove()
    }


}

