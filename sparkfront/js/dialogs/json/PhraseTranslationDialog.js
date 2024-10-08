class PhraseTranslationDialog extends JSONDialog {

    constructor() {
        super();

        this.req.setResponder("translator");

        this.textID = -1;

        this.phraseInput = this.element.querySelector("[name='phrase']");
        this.translationInput = this.element.querySelector("[name='translation']");
    }

    buttonAction(action) {

        if (action == "Translate") {

            this.store();

        } else if (action == "Close") {

            this.remove();
        }
    }

    getTableCell(textID, trID=null)
    {
        let cellSelector = `[relation='translation'][textID='${textID}']`;
        if (trID) {
            cellSelector+= `[trID='${trID}']`;
        }
        return document.querySelector(cellSelector);
    }
    /**
     * Process the result of the backend responder
     */
    processResult(responder, funct, result) {

        let jsonResult = result.json_result;

        let message = jsonResult.message;

        if (funct == "fetch") {

            this.phraseInput.value = jsonResult.phrase;
            this.translationInput.value = jsonResult.translation;

        }
        else if (funct == "store") {

            const html = "<span>" + this.translationInput.value + "</span>";

            const textID = this.req.getParameter("textID");
            const trID = this.req.getParameter("trID");
            const cell = this.getTableCell(textID, trID);

            cell.setAttribute("trID", jsonResult.trID);
            cell.innerHTML = html;

            showAlert(message);

            this.remove();

        }
        else if (funct == "clear") {

            const textID = this.req.getParameter("textID");
            const trID = this.req.getParameter("trID");
            const cell = this.getTableCell(textID, trID);

            cell.setAttribute("trID", -1);
            cell.innerHTML = "";

            showAlert(message);

        }
    }

    edit(textID) {

        this.textID = textID;

        const cell = this.getTableCell(textID);
        const trID = cell.getAttribute("trID");

        this.langID = this.element.getAttribute("langID");

        this.req.setFunction("fetch");

        this.req.setParameter("textID", textID);
        this.req.setParameter("langID", this.langID);
        this.req.setParameter("trID", trID);

        this.req.clearPostParameters();

        this.req.start();

        this.show();
    }

    store() {

        //already set during edit/fetch

        this.req.setFunction("store");

        this.req.setPostParameter("translation", this.translationInput.value);

        this.req.start();

    }

    clear(textID) {

        const cell = this.getTableCell(textID);
        const trID = cell.getAttribute("trID");

        this.req.setFunction("clear");

        this.req.setParameter("trID", trID);
        this.req.setParameter("textID", textID);

        this.req.clearPostParameters();

        this.req.start();
    }


}

