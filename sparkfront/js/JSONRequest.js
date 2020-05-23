function JSONRequestResult() {
    this.time = new Date();
    this.json_result = null;
    this.status = null;
    this.response = null;

}

function JSONRequestError() {
    this.time = new Date();
    this.status = null;
    this.description = null;
    this.response = null;

}

/**
 * @constructor
 */
function JSONRequest() {

    /**
     * @type {XMLHttpRequest}
     */
    this.req = new XMLHttpRequest();
    if (!this.req) throw "XMLHttpRequest is not available";

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
    this.progress_display = null;

    this.parameters = new URLSearchParams();

    this.command = "";
    this.function = "";


}

/**
 * Set the backend responder command name
 * @param cmd {string}
 */
JSONRequest.prototype.setResponder = function (cmd) {

    this.command = cmd;
}

/**
 *
 * @returns {string}
 */
JSONRequest.prototype.getResponder = function () {
    return this.command;
}

/**
 * Set the backend responder function call name
 * @param type {string}
 */
JSONRequest.prototype.setFunction = function (type) {

    this.function = type;
}

JSONRequest.prototype.getFunction = function () {
    return this.function;
}

/**
 * Set parameter for responder function call
 * @param name {string}
 * @param value {string}
 */
JSONRequest.prototype.setParameter = function (name, value) {

    this.parameters.set(name, value);

}

JSONRequest.prototype.getParameter = function (name) {
    return this.parameters.get(name);
}

/**
 * Set this request to use POST http method
 * Sets paramter 'name' with value encodeURIComponent('value') to the post data parameters object
 * @param name {string}
 * @param value {string}
 */
JSONRequest.prototype.setPostParameter = function (name, value) {
    this.post_data.set(name, value);
}

/**
 *
 * @param name
 * @returns {string}
 */
JSONRequest.prototype.getPostParameter = function (name) {
    return this.post_data.get(name);
}

/**
 * Remove all post data parameters
 */
JSONRequest.prototype.clearPostParameters = function () {
    this.post_data = new URLSearchParams();
}

/**
 * Returns the complete URL including the responder, function, and function parameters
 * @returns {URL}
 */
JSONRequest.prototype.getURL = function () {

    let url = new URL(this.url.href);
    url.searchParams.set("cmd", this.command);
    url.searchParams.set("type", this.function);

    this.parameters.forEach(function (value, key, parent) {
        url.searchParams.set(key, value);
    });

    return url;
}

JSONRequest.prototype.setInterval = function (msec) {
    this.interval = msec;
}

JSONRequest.prototype.stop = function () {
    if (this.req) {
        this.req.abort();
    }
}

/**
 * Start the http request
 * @param on_success {function(JSONRequestResult)}
 * @param on_error {function(JSONRequestError)}
 */
JSONRequest.prototype.start = function (on_success, on_error) {

    let responderURL = this.getURL();

    let logstr = "JSONRequest::start() - Responder: " + this.command + " Function: " + this.function + "\r\n";
    logstr += "Parameters:\r\n";
    this.parameters.forEach(function (value, key, parent) {
        logstr += "\t" + key + " => " + value + "\r\n";

    });

    console.log(logstr);


    this.req.onreadystatechange = function () {

        if (this.req.readyState != 4) return;

        var status = this.req.status;
        var response = this.req.responseText;

        try {

            if (status != 200) {
                throw "HTTP Error: " + status;
            }

            var json_result = JSON && JSON.parse(response) || $.parseJSON(response); //json_parse(ret);

            if (json_result.status != "OK") {
                throw json_result.message;
            }

            var request_result = new JSONRequestResult();
            request_result.json_result = json_result;
            request_result.status = status;
            request_result.response = response;

            if (typeof on_success === "function") {
                on_success(request_result);
            }


        } catch (err) {

            var description = (err.message ? err.message : err);

            if (typeof on_error === "function") {
                var request_error = new JSONRequestError();
                request_error.response = response;
                request_error.status = status;
                request_error.description = description; //
                on_error(request_error);
            } else {
                showAlert(description);
            }

        }

    }.bind(this);

    this.request_time = new Date();

    if (this.post_data.toString().length > 0) {
        console.log("Using POST: " + responderURL.href);
        this.req.open("POST", responderURL.href, this.async);
        this.req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        let post = "";
        this.post_data.forEach(function (value, key, parent) {
            post += key + "=" + encodeURIComponent(value);
            post += "&";
        });

        this.req.send(post);

    } else {
        console.log("Using GET: " + responderURL.href);
        this.req.open("GET", responderURL.href, this.async);
        this.req.send(null);
    }


}


