class JSONFormDialog extends ConfirmMessageDialog {

    constructor() {
        super();
        this.icon_enabled = false;
        this.text = "Loading please wait ...";

        this.setID("json_dialog");

        this.responderName = "";

        this.req = new JSONRequest();

        //Which selector holds the loaded content ie the view space
        this.contentSelector = " .Text";

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
            let result = request_result.json_result;
            this.loadContent(result.contents);
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

    //default implementation
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

    loadContent(contents)
    {
        $(this.visibleSelector() + " " + this.contentSelector).html(contents);
        this.modal_pane.centerContents();

        if (window.onload) {
            window.onload();
        }
    }
}