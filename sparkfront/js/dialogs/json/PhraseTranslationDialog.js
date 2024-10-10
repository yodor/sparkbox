class PhraseTranslationDialog extends JSONDialog {

    constructor() {
        super();

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

        const response = result.response;

        if (funct == "fetch") {

            this.phraseInput.value = response.phrase;
            this.translationInput.value = response.translation;

        }
        else if (funct == "store") {

            const html = "<span>" + this.translationInput.value + "</span>";

            const textID = this.request.getParameter("textID");
            const trID = this.request.getParameter("trID");
            const cell = this.getTableCell(textID, trID);

            cell.setAttribute("trID", response.trID);
            cell.innerHTML = html;

            showAlert(response.message);

            this.remove();

        }
        else if (funct == "clear") {

            const textID = this.request.getParameter("textID");
            const trID = this.request.getParameter("trID");
            const cell = this.getTableCell(textID, trID);

            cell.setAttribute("trID", -1);
            cell.innerHTML = "";

            showAlert(response.message);

        }
    }

    edit(textID) {

        this.textID = textID;

        const cell = this.getTableCell(textID);
        const trID = cell.getAttribute("trID");

        this.langID = this.element.getAttribute("langID");

        this.request.setFunction("fetch");

        this.request.setParameter("textID", textID);
        this.request.setParameter("langID", this.langID);
        this.request.setParameter("trID", trID);

        this.request.clearPostParameters();

        this.request.start();

        this.show();
    }

    store() {

        //already set during edit/fetch

        this.request.setFunction("store");

        this.request.setPostParameter("translation", this.translationInput.value);

        this.request.start();

    }

    clear(textID) {

        const cell = this.getTableCell(textID);
        const trID = cell.getAttribute("trID");

        this.request.setFunction("clear");

        this.request.setParameter("trID", trID);
        this.request.setParameter("textID", textID);

        this.request.clearPostParameters();

        this.request.start();
    }


}

