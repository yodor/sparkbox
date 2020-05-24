class JSONDialog extends MessageDialog {

    constructor() {
        super();
        this.req = new JSONRequest();

        this.req.onSuccess = this.onSuccess.bind(this);
        this.req.onError = this.onError.bind(this);
    }

    /**
     * Default onSuccess delegate for JSONRequest.onSuccess
     * @param request_result
     */
    onSuccess(request_result) {

        this.processResult(this.req.getResponder(), this.req.getFunction(), request_result);

    }

    onError(request_error) {

        this.processError(this.req.getResponder(), this.req.getFunction(), request_error);

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