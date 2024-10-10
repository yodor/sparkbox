class JSONDialog extends MessageDialog {

    /**
     * Assign responder with this.element 'responder' attribute value
     * Append observer function onRequestEvent to 'request'
     */
    constructor(templateID=null) {

        super(templateID);

        this.request = new JSONRequest();
        this.request.setResponder(""+this.element.getAttribute(JSONRequest.KEY_RESPONDER));

        this.request.onSuccess = this.onSuccess.bind(this);
        this.request.onError = this.onError.bind(this);

        const observer = this.onRequestEvent.bind(this);
        this.request.addObserver(observer);
    }

    /**
     * Observe JSONRequest events.
     * EVENT_STARTING set the 'loading' attribute
     * EVENT_FINISHED remove the 'loading' attribute
     * @param event {SparkEvent}
     */
    onRequestEvent(event)
    {
        if (event.isEvent(JSONRequest.EVENT_STARTING)) {
            this.element.setAttribute("loading", "");
        }
        else if (event.isEvent(JSONRequest.EVENT_FINISHED)) {
            this.element.removeAttribute("loading");
        }
    }

    /**
     * The backend responder cmd name
     * @param cmd {string}
     */
    setResponder(cmd) {
        this.request.setResponder(cmd);
        this.element.setAttribute("responder", cmd);
    }

    getJSONRequest() {
        return this.request;
    }


    /**
     * Default onSuccess delegate for JSONRequest.onSuccess
     * @param result {JSONRequestResult}
     */
    onSuccess(result) {

        this.processResult(this.request.getResponder(), this.request.getFunction(), result);

    }

    /**
     * Default onError delegate for JSONRequest.onError
     * @param error {JSONRequestError}
     */
    onError(error) {

        this.processError(this.request.getResponder(), this.request.getFunction(), error);

    }

    /**
     * Process the result made to the backend responder
     * @param responder {string}
     * @param funct {string}
     * @param result {JSONRequestResult}
     */
    processResult(responder, funct, result) {

    }

    /**
     *
     * @param responder {string}
     * @param funct {string}
     * @param result {JSONRequestError}
     */
    processError(responder, funct, result) {
        showAlert(result.description);
    }
}