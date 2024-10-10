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

    static EVENT_STARTING = "starting";
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

        this.xmlRequest.onreadystatechange = this.onReadyStateChange.bind(this);

        this.xmlRequest.upload.onprogress = this.onProgress.bind(this);
        /**
         * @type {URL}
         */
        this.url = new URL(window.location.href);
        this.url.searchParams.set(JSONRequest.KEY_JSONREQUEST, "1");

        this.interval = -1;
        this.status = 0;

        this.post_data = new URLSearchParams();

        this.async = true;

        this.parameters = new URLSearchParams();

        this.responder = "";
        this.function = "";

        this.form_data = null;
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
     * @param name
     * @returns {string}
     */
    getParameter(name) {
        return this.parameters.get(name);
    }

    /**
     * Remove all function call parameters
     */
    clearParameters() {
        this.parameters = new URLSearchParams();
    }

    /**
     * Remove function call parameter named 'name'
     * @param name
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
     * @param name
     * @returns {string}
     */
    getPostParameter(name) {
        return this.post_data.get(name);
    }

    /**
     *
     * @param form_data FormData
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
     * Returns the complete URL including the responder, function, and function parameters
     * @returns {URL}
     */
    getURL() {

        let url = new URL(this.url.href);
        url.searchParams.set(JSONRequest.KEY_RESPONDER, this.responder);
        url.searchParams.set(JSONRequest.KEY_FUNCTION, this.function);

        this.parameters.forEach(function (value, key, parent) {
            url.searchParams.set(key, value);
        });

        return url;
    }

    setInterval(msec) {
        this.interval = msec;
    }

    stop() {
        if (this.xmlRequest) {
            this.xmlRequest.abort();
        }
    }

    /**
     * Start the HTTP request
     */
    start() {

        this.notify(new SparkEvent(JSONRequest.EVENT_STARTING, this));

        const responderURL = this.getURL();

        //console.log(`JSONRequest::start() - ${this.responder}::${this.function}`);

        this.request_time = new Date();

        if (this.post_data.toString().length > 0 || this.form_data != null) {

            //console.log("Using POST: " + responderURL.href);
            this.xmlRequest.open("POST", responderURL.href, this.async);

            if (this.form_data == null) {
                this.form_data = new FormData();
            }
            this.post_data.forEach(function (value, key, parent) {
                this.form_data.append(key, value);
            }.bind(this));
            this.xmlRequest.send(this.form_data);


        } else {
            //console.log("Using GET: " + responderURL.href);
            this.xmlRequest.open("GET", responderURL.href, this.async);
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
        showAlert(error.description);
    }

    /**
     * Notify observers for progress
     * @param event {ProgressEvent}
     */
    onProgress(event) {
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
    }

    // 0	UNSENT	Client has been created. open() not called yet.
    // 1	OPENED	open() has been called.
    // 2	HEADERS_RECEIVED	send() has been called, and headers and status are available.
    // 3	LOADING	Downloading; responseText holds partial data.
    // 4	DONE	The operation is complete.
    onReadyStateChange() {

        if (this.xmlRequest.readyState === 1) {
            this.notify(new SparkEvent(JSONRequest.EVENT_STARTED, this));
            return;
        }

        if (this.xmlRequest.readyState !== 4) return;


        let status = this.xmlRequest.status;
        let response = this.xmlRequest.responseText;

        try {

            //protocol error
            if (status !== 200) {
                throw "HTTP Error: " + status;
            }

            //let isObject = response.constructor === Object;
            if (response.constructor === String) {
                response = JSON && JSON.parse(response);
            }

            //JSONResponse default properties are
            // name = responder name,
            // status should be OK for success and Error for error,
            // message generic message text,
            // generic contents payload result

            //Responder error
            if (response.status !== "OK") {
                throw response.message;
            }

            //Accept ok only from same name
            if (response.name !== this.getResponder()) {
                throw `Responder result name mismatch: response.name=${response.name} | request.responder=${this.getResponder()}`;
            }

            const result = new JSONRequestResult();
            //assign the response object
            result.response = response;

            const event = new SparkEvent(JSONRequest.EVENT_SUCCESS, this);
            event.result = result;
            this.notify(event);

            this.onSuccess(result);

        } catch (err) {

            const description = (err.message ? err.message : err);

            const requestError = new JSONRequestError();
            requestError.status = status;
            requestError.description = description;

            const event = new SparkEvent(JSONRequest.EVENT_ERROR, this);
            event.error = requestError;
            this.notify(event);

            this.onError(requestError);
        }

        this.notify(new SparkEvent(JSONRequest.EVENT_FINISHED, this));
    }
}
