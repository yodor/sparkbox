function BeanTranslationDialog() {

    this.req = new JSONRequest();
    this.langID = -1;
    this.field_name = "";
    this.bean_class = "";
    this.bean_id = "";
    this.req.async = true;
    this.language_alert = "Please select translation language";

    this.modal_pane = new ModalPopup();
    this.translator_editor = null;
    this.original_editor = null;

}

BeanTranslationDialog.prototype.attachWith = function (panel_id) {

    if (!panel_id) {
        showAlert("BeanTranslationDialog need panel_id to attach with");

    }
    this.cls = panel_id;


}

BeanTranslationDialog.prototype.show = function (field_name, is_mce) {

    this.is_mce = is_mce;

    instance = this;

    this.modal_pane.showID(this.cls);

    //bean_id, field_name, bean_class
    var bean_id = $(".InputFormView").attr("editID");
    var bean_class = $(".InputFormView").attr("bean");


    var popup = this.modal_pane.popup();

    this.langID = -1;

    //set first from combo box choosing language
    popup.find("[name='langID']").val("");


    popup.find("[action]").unbind("click");

    popup.find("[action='Translate']").click(function () {
        this.store();
    }.bind(this));

    popup.find("[action='Clear']").click(function () {
        this.clear();
    }.bind(this));

    popup.find("[action='Close']").click(function () {
        this.finish();
    }.bind(this));

    popup.find("[name='langID']").change(function () {
        this.changeLanguage();
    }.bind(this));

    popup.find(".caption [action='close']").click(function () {
        this.finish();
    }.bind(this));


    var source_content = $("FORM [name='" + field_name + "']").val();

    popup.find(".original_text").val(source_content);
    popup.find("[name='translation']").val("");

    if (is_mce) {

        var mce = new MCETextArea();
        mce.component_class = "TEXTAREA";

        mce.onEditorInit = function (editor) {

            editor.setMode('readonly');
            instance.original_editor = editor;

            popup.find(".original_text").prev().find(".mce-toolbar-grp").css("display", "none");

        };

        mce.attachWith("original_text");


        var mce1 = new MCETextArea();
        mce1.component_class = "TEXTAREA";
        mce1.attachWith("translation");
        mce1.onEditorInit = function (editor) {

            instance.translator_editor = editor;
            console.log("Translator Init done: " + editor.id);

            editor.getBody().setAttribute('contenteditable', false);

            editor.setContent("");
        };


    }


    this.field_name = field_name;
    this.bean_class = bean_class;
    this.bean_id = bean_id;

    this.req.progress_display = popup.find(".AjaxProgress");


}

BeanTranslationDialog.prototype.store = function () {

    var popup = this.modal_pane.popup();

    var frm = popup.find("form").get(0);

    this.req.setURL("?ajax=1&cmd=bean_translator&type=store&langID=" + this.langID + "&field_name=" + this.field_name + "&beanID=" + this.bean_id + "&bean_class=" + this.bean_class);

    this.req.post_data = formtostr(frm);

    this.req.start(
        function (request_result) {
            showAlert(request_result.json_result.message);
        }
    );

}

BeanTranslationDialog.prototype.changeLanguage = function () {

    this.langID = parseInt(this.modal_pane.popup().find("[name='langID']").val());

    var translation_area = this.modal_pane.popup().find("[name='translation']");

    translation_area.val("");

    if (this.langID < 1) return;

    this.fetch();

}

BeanTranslationDialog.prototype.fetch = function () {

    if (this.langID < 1) {
        showAlert(this.language_alert);
        return;
    }

    //var instance = this;

    var url = "?ajax=1&cmd=bean_translator&type=fetch&langID=" + this.langID + "&field_name=" + this.field_name + "&beanID=" + this.bean_id + "&bean_class=" + this.bean_class;

    this.req.setURL(url);

    this.req.post_data = null;
    this.req.start(
        function (request_result) {

            var result = request_result.json_result;

            if (!result.translation) showAlert(result.message);

            if (this.translator_editor) this.translator_editor.getBody().setAttribute('contenteditable', true);

            var popup = this.modal_pane.popup();
            popup.find("[name='translation']").val(result.translation);

        }.bind(this)
    );


}

BeanTranslationDialog.prototype.clear = function () {
    if (this.langID < 1) {
        showAlert(this.language_alert);
        return;
    }

    this.req.setURL("?ajax=1&cmd=bean_translator&type=clear&langID=" + this.langID + "&field_name=" + this.field_name + "&beanID=" + this.bean_id + "&bean_class=" + this.bean_class);

    this.req.post_data = null;
    this.req.start(
        function (request_result) {

            var result = request_result.json_result;
            var popup = this.modal_pane.popup();
            popup.find("[name='translation']").val("");

            if (this.translator_editor) this.translator_editor.setContent("");

            showAlert(result.message);

        }.bind(this)
    );
}

BeanTranslationDialog.prototype.finish = function () {
    var popup = this.modal_pane.popup();

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


    $(".BeanTranslationPopup").last().find("[name='langID']").val("");
    this.modal_pane.pane().remove();
}




