class JSONRequestResult  {
    constructor() {
        this.time = new Date();
        this.json_result = null;
        this.status = null;
        //this.response = null;
    }
}

class JSONRequestError extends JSONRequestResult {

    constructor() {
        super();
        this.description = null;
    }

}

class JSONRequest extends SparkObject {

    static EVENT_STARTING = "starting";
    static EVENT_STARTED = "started";
    static EVENT_SUCCESS = "success";
    static EVENT_ERROR = "error";
    static EVENT_PROGRESS = "progress";
    static EVENT_FINISHED = "finished";

    constructor() {
        super();

        this.request_result = null;
        this.request_error = null;

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
        this.url.searchParams.set("JSONRequest", "1");

        this.interval = -1;
        this.status = 0;

        this.post_data = new URLSearchParams();

        this.async = true;
        this.request_time = null;

        this.parameters = new URLSearchParams();

        this.command = "";
        this.function = "";

        this.form_data = null;
    }

    /**
     * Set the backend responder command name
     * @param cmd {string}
     */
    setResponder(cmd) {

        this.command = cmd;
    }

    /**
     * Return the  backend responder command name
     * @returns {string}
     */
    getResponder() {
        return this.command;
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
        url.searchParams.set("cmd", this.command);
        url.searchParams.set("type", this.function);

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
     * @param on_success {function(JSONRequestResult)}
     * @param on_error {function(JSONRequestError)}
     */
    start() {

        this.request_result = null;
        this.request_error = null;

        this.notify(new SparkEvent(JSONRequest.EVENT_STARTING, this));

        let responderURL = this.getURL();

        let logstr = "JSONRequest::start() - Responder: " + this.command + " Function: " + this.function + "\r\n";
        console.log(logstr);

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
     * Executed after start and there is no error during the request
     * @param result {JSONRequestResult}
     */
    onSuccess(result) {

    }

    /**
     * Executed after start and there is error
     * @param result {JSONRequestError}
     */
    onError(result) {
        showAlert(result.description);
    }

    /**
     *
     * @param event ProgressEvent
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

            if (status !== 200) {
                throw "HTTP Error: " + status;
            }

            //let isObject = response.constructor === Object;

            let json_response = response;

            if (response.constructor === String) {
                json_response = JSON && JSON.parse(response);
            }

            if (json_response.status !== "OK") {
                throw json_response.message;
            }

            let request_result = new JSONRequestResult();
            request_result.json_result = json_response;
            request_result.status = status;
            request_result.response = response;
            this.request_result = request_result;

            this.notify(new SparkEvent(JSONRequest.EVENT_SUCCESS, this));
            this.onSuccess(request_result);

        } catch (err) {

            let description = (err.message ? err.message : err);

            let request_error = new JSONRequestError();
            request_error.response = response;
            request_error.status = status;
            request_error.description = description; //
            this.request_error = request_error;

            this.notify(new SparkEvent(JSONRequest.EVENT_ERROR, this));
            this.onError(request_error);
        }

        this.notify(new SparkEvent(JSONRequest.EVENT_FINISHED, this));
    }
}
