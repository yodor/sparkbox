class JSONFormDialog extends ConfirmMessageDialog {

    constructor() {
        super();
        this.loader = "<div class='lds-facebook'><div></div><div></div><div></div></div>";
        this.icon_enabled = false;
        this.text = this.loader;

        this.setID("json_dialog");

        this.responderName = "";

        this.req = new JSONRequest();

        //Which selector holds the loaded content ie the view space
        this.contentSelector = " .Text";

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
            this.setText(this.loader);
            $(window).resize();
        }
    }

    /**
     * The backend responder cmd name
     * @param responder_name string
     */
    setResponder(responder_name) {
        this.responderName = responder_name;
        this.req.setResponder(responder_name);
    }

    getJSONRequest() {
        return this.req;
    }

    show() {
        super.show();
        $(this.visibleSelector()).attr("handler", this.responderName);
        this.req.setResponder(this.responderName);
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

        let form = $(this.visibleSelector()+" FORM").get(0);
        let name = form.getAttribute("name");
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
        let result = request_result.json_result;
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

    loadContent(contents)
    {
        $(this.visibleSelector() + " " + this.contentSelector).html(contents);

        //trigger onPageLoad() handlers
        document.tooltip.assignListeners(document.querySelector(this.visibleSelector() + " " + this.contentSelector));
        dispatchEvent(new Event('load'));
    }


}
