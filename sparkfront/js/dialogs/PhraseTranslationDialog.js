class PhraseTranslationDialog extends JSONDialog {

    constructor() {
        super();

        this.setID("phrase_translator");

        this.req.setResponder("translator");

        this.textID = -1;

    }

    buttonAction(action) {

        if (action == "Translate") {

            this.store();

        } else if (action == "Close") {

            this.modal_pane.close();
        }
    }

    /**
     * Process the result of the backend responder
     */
    processResult(responder, funct, result) {

        let jsonResult = result.json_result;

        let message = jsonResult.message;

        if (funct == "fetch") {

            this.modal_pane.popup.find("[name='phrase']").val(jsonResult.phrase);
            this.modal_pane.popup.find("[name='translation']").val(jsonResult.translation);
        }
        else if (funct == "store") {

            let translation = this.modal_pane.popup.find("[name=translation]");

            var html = "<span>" + translation.val() + "</span>";

            let textID = this.req.getParameter("textID");
            let trID = this.req.getParameter("trID");

            let cell = $("[relation='translation'][trID='" + trID + "'][textID='" + textID + "']");

            cell.html(html);
            cell.attr("trID", jsonResult.trID);

            showAlert(message);

            this.modal_pane.close();
        }
        else if (funct == "clear") {

            let textID = this.req.getParameter("textID");
            let trID = this.req.getParameter("trID");

            let cell = $("[relation='translation'][trID='" + trID + "'][textID='" + textID + "']");

            cell.attr("trID", -1);
            cell.html("");

            showAlert(message);

            this.modal_pane.close();
        }
    }

    edit(textID) {

        this.textID = textID;
        this.req.setParameter("textID", textID);

        this.show();

        let cell = $("[column='translation'][textID='" + textID + "']");
        let trID = cell.attr("trID");

        this.langID = this.modal_pane.popup.attr("langID");
        this.req.setParameter("langID", this.langID);

        this.req.setParameter("trID", trID);

        this.req.setFunction("fetch");

        this.req.clearPostParameters();

        this.req.start();
    }

    store() {

        //already set during edit/fetch

        this.req.setFunction("store");

        let translation = this.modal_pane.popup.find("[name=translation]");

        this.req.setPostParameter("translation", translation.val());

        this.req.start();

    }

    clear(textID) {

        var cell = $("[relation='translation'][textID='" + textID + "']");
        var trID = cell.attr("trID");

        this.req.setFunction("clear");

        this.req.setParameter("trID", trID);
        this.req.setParameter("textID", textID);

        this.req.clearPostParameters();

        this.req.start();
    }


}

