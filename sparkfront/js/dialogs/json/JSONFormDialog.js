class JSONFormDialog extends ConfirmMessageDialog {

    constructor() {
        super();
        this.loader = "<div class='lds-facebook'><div></div><div></div><div></div></div>";

        this.req = new JSONRequest();

        this.req.setResponder(this.element.getAttribute("handler"));

        const observer = this.onRequestEvent.bind(this);
        this.req.addObserver(observer);

    }

    /**
     *
     * @param sparkEvent {SparkEvent}
     */
    onRequestEvent(event)
    {

        if (event.isEvent(JSONRequest.EVENT_STARTING)) {
            this.element.setAttribute("loading", "");
            this.setContent(this.loader);
        }
        else if (event.isEvent(JSONRequest.EVENT_FINISHED)) {
            this.element.removeAttribute("loading");
        }
    }

    /**
     * The backend responder cmd name
     * @param responder_name string
     */
    setResponder(responder_name) {
        this.req.setResponder(responder_name);
    }

    getJSONRequest() {
        return this.req;
    }

    show() {
        super.show();

        this.req.setFunction("render");

        this.req.onSuccess = function(request_result) {
            this.processRenderResult(request_result);
        }.bind(this);

        this.req.start();
    }


    buttonAction(action) {

        if (action == "confirm") {

            this.submitForm();

        } else if (action == "cancel") {

            this.cancelForm();

        }

    }

    submitForm()  {

        //console.log("Submitting form");
        this.req.setFunction("submit");

        const form = this.element.querySelector("FORM");
        const name = form.getAttribute("name");

        this.req.setPostParameter("SubmitForm", name);
        this.req.setPostFormData(new FormData(form));

        this.req.onSuccess = function(request_result) {
            this.processSubmitResult(request_result, name);
        }.bind(this);

        this.req.start();
    }

    cancelForm() {
        this.remove();
    }

    //subsequent rendering after form submit
    processSubmitResult(request_result, form_name) {
        const result = request_result.json_result;
        if (result.contents) {
            this.loadContent(result.contents)
            showAlert(result.message);
        }
        else {
            this.remove();
            showAlert(result.message);
        }
    }

    //initial rendering during show()
    processRenderResult(request_result)
    {
        let result = request_result.json_result;
        this.loadContent(result.contents);
    }

    loadContent(contents) {

        const loadedContent = document.templateFactory.createElement(contents);
        const dialogContent = this.contentElement();

        dialogContent.innerHTML = "";
        dialogContent.append(loadedContent);

        const event = new SparkEvent(SparkEvent.DOM_UPDATED);
        event.source = dialogContent;

        document.dispatchEvent(event);
    }

}
