class JSONRequestResult  {
    constructor() {
        this.time = new Date();

        /**
         * JSONResponse status or protocol error number
         * @type {string}
         */
        this.status = "";

        /**
         * JSONResponse object returned from backend responder
         * @type {object}
         */
        this.response = {};
    }
}

class JSONRequestError extends JSONRequestResult {

    constructor() {
        super();

        /**
         * Error description
         * @type {string}
         */
        this.description = "";
    }

}

class JSONRequest extends SparkObject {

    static EVENT_STARTED = "started";
    static EVENT_SUCCESS = "success";
    static EVENT_ERROR = "error";
    static EVENT_PROGRESS = "progress";
    static EVENT_FINISHED = "finished";

    //match const in RequestResponder.php
    static KEY_RESPONDER = "responder";

    //match const in JSONResponder.php
    static KEY_FUNCTION = "function";
    static KEY_JSONREQUEST = "JSONRequest";

    constructor() {
        super();

        /**
         * @type {XMLHttpRequest}
         */
        this.xmlRequest = new XMLHttpRequest();
        if (!this.xmlRequest) throw "XMLHttpRequest is not available";

        this.xmlRequest.addEventListener("loadstart", (event)=>
            this.notify(new SparkEvent(JSONRequest.EVENT_STARTED, this))
        );

        this.xmlRequest.addEventListener("load", (event)=>
            this.onLoad(event)
        );

        this.xmlRequest.addEventListener("error", (event)=>
            this.emitError(this.xmlRequest.statusText, this.xmlRequest.status)
        );

        this.xmlRequest.addEventListener("abort", (event)=>
            this.emitError("Aborted", 0)
        );

        this.xmlRequest.addEventListener("loadend", (event)=>
            this.notify(new SparkEvent(JSONRequest.EVENT_FINISHED, this))
        );

        this.xmlRequest.upload.addEventListener("progress", (event)=> {
            let progress_event = new SparkEvent(JSONRequest.EVENT_PROGRESS, this);
            progress_event.computable = event.lengthComputable;
            progress_event.loaded = event.loaded;
            progress_event.total = event.total;
            let percent = 0;
            if (progress_event.computable && event.total>0) {
                percent = (event.loaded / event.total) * 100;
            }
            progress_event.percent = percent;
            this.notify(progress_event);
        });


        /**
         * Request POST parameters
         * @type {URLSearchParams}
         */
        this.post_data = new URLSearchParams();

        /**
         * Request URL parameters
         * @type {URLSearchParams}
         */
        this.parameters = new URLSearchParams();

        /**
         *
         * @type {string}
         */
        this.responder = "";
        /**
         *
         * @type {string}
         */
        this.function = "";

        /**
         *
         * @type {FormData}
         */
        this.form_data = null;
    }

    emitError(description, status)
    {
        const requestError = new JSONRequestError();
        requestError.status = status;
        requestError.description = description;

        const sparkEvent = new SparkEvent(JSONRequest.EVENT_ERROR, this);
        sparkEvent.error = requestError;
        this.notify(sparkEvent);

        this.onError(requestError);
    }
    /**
     * Returns the complete URL including the responder, function, and function parameters
     * @returns {URL}
     */
    getURL() {

        const url = new URL(window.location.href);
        url.searchParams.set(JSONRequest.KEY_JSONREQUEST, "1");
        url.searchParams.set(JSONRequest.KEY_RESPONDER, this.responder);
        url.searchParams.set(JSONRequest.KEY_FUNCTION, this.function);

        this.parameters.forEach(function (value, key, parent) {
            url.searchParams.set(key, value);
        });

        return url;
    }

    /**
     * Start the HTTP request
     */
    start() {

        const responderURL = this.getURL();

        //console.log(`JSONRequest::start() - ${this.responder}::${this.function}`);

        if (this.post_data.toString().length > 0 || this.form_data != null) {

            //console.log("Using POST: " + responderURL.href);
            let form_data = this.form_data;

            if (form_data == null) {
                form_data = new FormData();
            }

            this.post_data.forEach((value, key, urlSearchParams) => {
                form_data.append(key, value);
            });

            this.xmlRequest.open("POST", responderURL, true);
            this.xmlRequest.send(form_data);


        } else {
            //console.log("Using GET: " + responderURL.href);
            this.xmlRequest.open("GET", responderURL, true);
            this.xmlRequest.send(null);

        }

    }

    /**
     * Default delegate handler for successful response
     * Does nothing by default
     * @param result {JSONRequestResult}
     */
    onSuccess(result) {

    }

    /**
     * Default delegate handler for error response
     * Shows alert with error.description
     * @param error {JSONRequestError}
     */
    onError(error) {
        showAlert(`Error: ${error.description} - Status: ${error.status}`);
    }

    onLoad() {

        //HTTP protocol status code
        let status = this.xmlRequest.status;
        let response = this.xmlRequest.responseText;

        try {

            //protocol error
            if (status !== 200) {
                throw new Error(this.xmlRequest.responseText);
            }

            //let isObject = response.constructor === Object;
            try {
                if (response.constructor === String) {
                    response = JSON && JSON.parse(response);
                }
            }
            catch (e) {
                throw new Error("Incorrect JSON response: " + e.message);
            }

            //JSONResponse default properties are
            // name = responder name,
            // status should be OK for success and Error for error,
            // message generic message text,
            // generic contents payload result

            //Responder error
            if (response.status !== "OK") {
                throw new Error(response.message);
            }

            //Accept ok only from same name
            if (response.name !== this.getResponder()) {
                throw new Error(`Responder result name mismatch: response.name=${response.name} | request.responder=${this.getResponder()}`);
            }

            const result = new JSONRequestResult();
            //assign the response object
            result.response = response;

            const event = new SparkEvent(JSONRequest.EVENT_SUCCESS, this);
            event.result = result;
            this.notify(event);

            this.onSuccess(result);

        } catch (err) {
            this.emitError((err.message ? err.message : err), status);
        }


    }



    /**
     * Set the backend responder class name
     * @param responder {string}
     */
    setResponder(responder) {

        this.responder = responder;
    }

    /**
     * Return the  backend responder command name
     * @returns {string}
     */
    getResponder() {
        return this.responder;
    }

    /**
     * Set the backend responder function call name
     * @param type {string}
     */
    setFunction(type) {

        this.function = type;
    }

    /**
     * Return the backend responder function call name
     * @returns {string}
     */
    getFunction() {
        return this.function;
    }

    /**
     * Set parameter for responder function call
     * @param name {string}
     * @param value {string}
     */
    setParameter(name, value) {

        this.parameters.set(name, value);

    }

    /**
     * Get the value of the function call parameter named 'name'
     * @param name {string}
     * @returns {string}
     */
    getParameter(name) {
        return this.parameters.get(name);
    }

    /**
     * Remove all function call query parameters
     */
    clearParameters() {
        this.parameters = new URLSearchParams();
    }

    /**
     * Remove function call parameter named 'name'
     * @param name {string}
     */
    removeParameter(name) {
        this.parameters.delete(name);
    }

    /**
     * Set POST parameter named 'name' with value 'value'
     * @param name {string}
     * @param value {string}
     */
    setPostParameter(name, value) {
        this.post_data.set(name, value);
    }

    /**
     * Append POST parameter named 'name' with value 'value'
     * @param name {string}
     * @param value {string}
     */
    addPostParameter(name, value) {
        this.post_data.append(name, value);
    }

    /**
     * Get the POST parameter named 'name' value
     * @param name {string}
     * @returns {string}
     */
    getPostParameter(name) {
        return this.post_data.get(name);
    }

    /**
     *
     * @param form_data {FormData}
     */
    setPostFormData(form_data) {
        this.form_data = form_data;
    }

    /**
     * FormData
     * @returns {FormData}
     */
    getPostFormData() {
        return this.form_data;
    }

    /**
     * Remove all POST parameters
     */
    clearPostParameters() {
        this.post_data = new URLSearchParams();
    }

    clearPostFormData() {
        this.form_data = null;
    }

    /**
     * Clear post_data and set from_data to null
     */
    clearPost() {
        this.clearPostFormData();
        this.clearPostParameters();
    }

}
