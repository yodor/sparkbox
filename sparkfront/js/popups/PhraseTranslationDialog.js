function PhraseTranslationDialog() {

    this.req = new JSONRequest();

    this.req.async = true;

    this.modal_pane = new ModalPopup();

}

PhraseTranslationDialog.prototype.attachWith = function (panel_id) {
    if (!panel_id) {
        showAlert("PhraseTranslationDialog need panel_id to attach with");

    }
    this.cls = panel_id;

}


PhraseTranslationDialog.prototype.edit = function (textID) {

    this.textID = textID;

    var cell = $("[relation='translation'][textID='" + textID + "']");
    var trID = cell.attr("trID");

    this.modal_pane.showID(this.cls);

    var popup = this.modal_pane.popup();

    this.langID = popup.attr("langID");

    popup.find("[action]").unbind("click");


    popup.find("[action='Translate']").bind("click", function () {
        this.store();
    }.bind(this));


    popup.find("[action='Close']").bind("click", function () {
        this.finish();
    }.bind(this));


    popup.find(".caption [action='close']").bind("click", function () {
        this.finish();
    }.bind(this));

    popup.data("textID", textID);
    popup.data("trID", trID);

    this.req.progress_display = popup.find(".AjaxProgress");

    this.req.setURL("?ajax=1&cmd=translator&type=fetch&langID=" + this.langID + "&textID=" + textID + "&trID=" + trID);
    this.req.post_data = null;

    this.req.start(
        function (request_result) {
            var result = request_result.json_result;
            popup.find(".original_text").val(result.original_text);
            popup.find("[name='translation']").val(result.translation);
        }
    );
}


PhraseTranslationDialog.prototype.store = function () {


    var popup = this.modal_pane.popup();

    var textID = popup.data("textID");
    var trID = popup.data("trID");

    var frm = popup.find("form").get(0);

    this.req.setURL("?ajax=1&cmd=translator&type=store&langID=" + this.langID + "&textID=" + textID + "&trID=" + trID);

    this.req.post_data = formtostr(frm);

//   console.log("trID="+trID);

    var cell = $("[relation='translation'][trID='" + trID + "'][textID='" + textID + "']");

    this.req.start(
        function (request_result) {
            var result = request_result.json_result;
            var message = result.message;
            var popup = this.modal_pane.popup();

            var html = "<span>" + popup.find("[name='translation']").val() + "</span>";

            cell.html(html);
            cell.attr("trID", result.trID);

            this.modal_pane.pane().remove();
            showAlert(message);
        }.bind(this)
    );

}
PhraseTranslationDialog.prototype.clear = function (textID) {

    var cell = $("[relation='translation'][textID='" + textID + "']");
    var trID = cell.attr("trID");

    this.req.setURL("?ajax=1&cmd=translator&type=clear&langID=" + this.langID + "&textID=" + textID + "&trID=" + trID);
    this.req.post_data = null;

    this.req.start(
        function (request_result) {
            var result = request_result.json_result;
            cell.attr("trID", -1);
            cell.html("");
            showAlert(result.message);
        }
    );
}
PhraseTranslationDialog.prototype.finish = function () {
    this.modal_pane.pane().remove();
}
