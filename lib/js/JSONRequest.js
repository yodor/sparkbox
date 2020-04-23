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

function JSONRequest(url) {
    this.url = url;
    this.interval = -1;
    this.req = new XMLHttpRequest();
    this.status = 0;
    this.post_data = null;
    this.progress_display = null;
    this.async = true;
    this.request_time = null;

    if (!this.req) throw "Can not instantiate XMLHttpRequest";

}

JSONRequest.prototype.setPostData = function (params) {
    this.post_data = params;

}
JSONRequest.prototype.setURL = function (url) {
    this.url = url;
}
JSONRequest.prototype.appendURL = function (url) {
    var current_get = window.location.search.substring(1);
    var append_url = "?" + current_get + "&" + url;
    this.url = append_url;
}
JSONRequest.prototype.setInterval = function (msec) {
    this.interval = msec;
}
JSONRequest.prototype.stop = function () {
    if (this.req) {
        this.req.abort();
    }
}
JSONRequest.prototype.start = function (func_success, func_error) {

    console.log("JSONRequest::start with URL: " + this.url);

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

            if (typeof func_success === "function") {
                func_success(request_result);
            }


        } catch (err) {

            var description = (err.message ? err.message : err);

// 	  console.log("Request Error: " + description);

            if (typeof func_error === "function") {
                var request_error = new JSONRequestError();
                request_error.response = response;
                request_error.status = status;
                request_error.description = description; //
                func_error(request_error);
            } else {
                showAlert(description);
            }

        }
        return;

    }.bind(this);

    this.request_time = new Date();

    if (this.post_data) {
        this.req.open("POST", this.url, this.async);
        this.req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        this.req.setRequestHeader("Content-length", this.post_data.length);
        this.req.setRequestHeader("Connection", "close");
        this.req.send(this.post_data);
    } else {
        this.req.open("GET", this.url, this.async);
        this.req.send(null);
    }


}


