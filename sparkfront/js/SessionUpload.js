class SessionUpload extends Component {

    constructor() {
        super();
        this.setClass(".SessionUpload");

        this.req = new JSONRequest();

        const observer = this.onRequestEvent.bind(this);
        this.req.addObserver(observer);

        this.req.onError = this.processError.bind(this);
    }

    input() {
        return $(this.selector() + " INPUT[type='file']");
    }

    controls(element) {
        if (!element)element = "";

        return $(this.selector() + " .Controls " + element);
    }

    initialize() {

        super.initialize();

        this.req.setResponder(this.component().attr("handler_command"));
        this.req.setParameter("field_name", this.field);

        this.component().data("upload_control", this);


        this.input().on("change", function (event) {
            this.uploadFileChanged(event);
        }.bind(this));

        let instance = this;
        let slots = this.component().find(".ArrayContents");
        slots.find("[action='Remove']").each(function (index) {
            $(this).on("click", function (event) {
                instance.removeSlot($(this));
            });
        })


    }



    onRequestEvent(event) {
        if (event.isEvent(JSONRequest.EVENT_STARTED)) {
            this.controls().attr("working", "1");
            this.updateProgress(0);
        } else if (event.isEvent(JSONRequest.EVENT_SUCCESS)) {
            this.controls().removeAttr("working");
        } else if (event.isEvent(JSONRequest.EVENT_ERROR)) {
            this.controls().removeAttr("working");
        } else if (event.isEvent(JSONRequest.EVENT_PROGRESS)) {
            this.updateProgress(event.percent);
        }

    }

    updateProgress(value)
    {
        let percentVal = parseInt(value)+"%";

        this.controls(".bar").width(percentVal);
        this.controls(".percent").html(percentVal);

    }
    uploadFileChanged(event) {

        console.log("SessionUpload::uploadFileChanged()");

        let fileInput = this.input().get(0);
        let upload_count = fileInput.files.length;
        if (upload_count<1) {
            //console.log("No file selected for upload");
            return;
        }
        //console.log("Upload count: " + upload_count);

        let max_slots = this.input().attr("max_slots");
        let slots = this.component().find(".ArrayContents");
        let controls = this.component().find(".Controls");

        //process current number of slots
        let active_slots = slots.children().length;
        if (active_slots >= max_slots) {

            showAlert("Maximum number of upload slots reached");
            this.resetFileInput();
            return;
        }

        if (active_slots + upload_count > max_slots) {
            showAlert("Select less files");
            this.resetFileInput();
            return;
        }

        this.req.setFunction("upload");

        this.req.onSuccess = this.processResult.bind(this);

        let fileData = new FormData();

        for (let a=0;a<fileInput.files.length;a++) {
            fileData.append(this.input().attr("name"), fileInput.files[a]);
        }

        this.req.setPostFormData(fileData);

        this.req.start();
    }


    /**
     *
      * @param result {JSONRequestError}
     */
    processError(result) {
        showAlert(result.description);
    }

    /**
     *
     * @param result {JSONRequestResult}
     */
    processResult(request_result) {
        //        console.log(result);
        let result = request_result.json_result;

        let slots = this.component().find(".ArrayContents");

        if (result.contents) showAlert(result.contents);

        for (let a = 0; a < result.object_count; a++) {
            let current_object = result.objects[a];
            slots.append(current_object.html);
        }

        let instance = this;

        slots.find("[action='Remove']").each(function (index) {
            $(this).on("click",function (event) {
                instance.removeSlot($(this));
            });
        });


        document.tooltip.assignListeners(this.element.querySelector(".ArrayContents"));
        this.resetFileInput();
    }

    resetFileInput() {

        this.input().wrap("<form>").closest("form").get(0).reset();
        this.input().unwrap();

    }

    /**
     * jquery element
     * @param elm
     */
    removeSlot(elm) {

        let uid = elm.parents(".Element").find("input[type='hidden']").attr("value");

        this.req.setFunction("remove");

        this.req.setParameter("uid", uid);

        this.req.onSuccess=function(request_result) {
            elm.parents(".Element").remove();
            document.tooltip.hide();
        };

        this.req.start();

    }
}
