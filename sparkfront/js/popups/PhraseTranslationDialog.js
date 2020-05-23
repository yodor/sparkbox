function PhraseTranslationDialog() {

    this.req = new JSONRequest();

    this.req.setResponder("translator");

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

    let cell = $("[column='translation'][textID='" + textID + "']");
    let trID = cell.attr("trID");

    this.modal_pane.showID(this.cls);

    let popup = this.modal_pane.popup();

    this.langID = popup.attr("langID");

    this.req.setParameter("langID", this.langID);

    popup.find("[action]").unbind("click");


    popup.find("[action='Translate']").bind("click", function () {
        this.store();
    }.bind(this));


    popup.find("[action='Close']").bind("click", function () {
        this.finish();
    }.bind(this));



    this.req.setParameter("textID", textID);
    this.req.setParameter("trID", trID);

    this.req.setFunction("fetch");

    this.req.clearPostParameters();

    this.req.start(
        function (request_result) {
            var result = request_result.json_result;
            popup.find("[name='phrase']").val(result.phrase);
            popup.find("[name='translation']").val(result.translation);
        }
    );
}


PhraseTranslationDialog.prototype.store = function () {


    let popup = this.modal_pane.popup();

    //already set during edit/fetch
    let textID = this.req.getParameter("textID");
    let trID = this.req.getParameter("trID");

    this.req.setFunction("store");

    let translation = popup.find("[name=translation]");
    this.req.setPostParameter("translation", translation.val());

    var cell = $("[relation='translation'][trID='" + trID + "'][textID='" + textID + "']");

    this.req.start(
        function (request_result) {

            var result = request_result.json_result;
            var message = result.message;

            var html = "<span>" + translation.val() + "</span>";

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

    this.req.setFunction("clear");

    this.req.clearPostParameters();

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
