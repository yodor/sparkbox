class JSONFormDialog extends JSONDialog {


    constructor(templateID=null) {
        //use confirm message dialog template
        super(templateID);

        this.loader = "<div class='lds-facebook'><div></div><div></div><div></div></div>";
    }

    /**
     * EVENT_STARTED replace this.content innerHTML with this.loader
     * @param event {SparkEvent}
     */
    onRequestEvent(event)
    {
        super.onRequestEvent(event);

        if (event.isEvent(JSONRequest.EVENT_STARTED)) {
            this.content.innerHTML = this.loader;
        }

    }


    /**
     * Show the dialog and call responder method 'render'
     * JSONRequest onSuccess points to processRenderResult
     */
    show() {
        super.show();

        this.request.setFunction("render");

        this.request.onSuccess = this.processRenderResult.bind(this);
        //a form that can not be shown: the dialog closes, the message is shown
        this.request.onError = (error) => {
            this.remove();
            showAlert(error.description);
        };

        this.request.start();
    }


    buttonAction(action) {

        if (action === "confirm") {

            this.submitForm();

        } else if (action === "cancel") {

            this.cancelForm();

        }

    }

    /**
     * Call responder method 'submit' posint the formdata created from the FORM inside this.element
     * JSONRequest onSuccess points to processSubmitResult
     */
    submitForm()  {

        //not while a request runs (a second click would send the form twice) or before the form is shown
        if (this.element.hasAttribute("loading")) return;
        const form = this.element.querySelector("FORM");
        if (!form) return;

        this.request.setFunction("submit");
        //an error of the submit: the message, the form stays
        this.request.onError = this.onError.bind(this);

        const name = form.getAttribute("name");

        this.request.setPostParameter("SubmitForm", name);
        this.request.setPostFormData(new FormData(form));

        this.request.onSuccess = function(result) {
            this.processSubmitResult(result, name);
        }.bind(this);

        this.request.start();
    }

    cancelForm() {
        this.remove();
    }

    /**
     * Process result of 'submit' responder method
     * If 'response.contents' is empty the dialog is closed if not loadContent is called using 'response.contents'
     * Alert message is shown using 'response.message'
     *
     * @param result {JSONRequestResult}
     * @param form_name {string} the name of the form
     */
    processSubmitResult(result, form_name) {

        const response = result.response;

        if (response.contents) {
            this.loadContent(response.contents)
            showAlert(response.message);
        }
        else {
            //the gtag commands of the response (a command or a list of them - an event and a conversion)
            if (response.gtm) {
                [].concat(response.gtm).forEach((gtm) => {
                    let sparkEvent = new SparkEvent(SparkEvent.GTM_EVENT);
                    sparkEvent.gtm = gtm;
                    document.dispatchEvent(sparkEvent);
                });
            }
            this.remove();
            showAlert(response.message);
        }

    }


    /**
     * Process result of 'render' responder method
     * 'response.contents' are loaded into 'this.content'
     *
     * @param result {JSONRequestResult}
     */
    processRenderResult(result) {

        this.loadContent(result.response.contents);

    }

    /**
     * Replace 'this.content' inner nodes with nodeList created from 'contents' html string
     *
     * @param contents {string} The html string received from the backend responder
     */
    loadContent(contents) {

        this.content.innerHTML = "";
        document.templateFactory.appendContent(this.content, contents);

        //Enter in a field sends the form through the dialog (not as a page request)
        this.content.querySelector("FORM")?.addEventListener("submit", (event) => {
            event.preventDefault();
            this.submitForm();
        });
        //the first field gets the focus - with a mouse only (on a phone it would open the keyboard at once)
        if (window.matchMedia("(pointer: fine)").matches) this.content.querySelector("FORM input:not([type='hidden']), FORM textarea, FORM select")?.focus();

    }

}