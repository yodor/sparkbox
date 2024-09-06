class SessionUpload extends Component {

    constructor() {
        super();
        this.setClass(".SessionUpload");

        this.req = new JSONRequest();

    }

    input() {
        return $(this.selector() + " INPUT[type='file']");
    }

    initialize() {

        super.initialize();


        this.req.setResponder(this.component().attr("handler_command"));
        this.req.setParameter("field_name", this.field);

        this.component().data("upload_control", this);


        this.input().on("change", function(event){
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

    uploadFileChanged(event) {

        console.log("SessionUpload::uploadFileChanged()");

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

        if (this.input().get(0).files) {

            let upload_count = this.input().get(0).files.length;
            if (active_slots + upload_count > max_slots) {
                showAlert("Select less files");
                this.resetFileInput();
                return;
            }
        }

        let form = this.prepareUploadForm();

        this.req.setFunction("upload");

        //let form = this.input().parents("FORM").first();

        //copy the request url
        form.attr("action", this.req.getURL().href);

        //console.log("Submitting form clone to URL: " + form.attr("action"));

        form.submit();

    }

    /**
     *
     * @returns {jQuery}
     */
    prepareUploadForm() {

        let controls = $(this.selector()).find(".Controls");
        let form = $(this.selector()).parents("FORM").first().clone();

        let progress = controls.find(".Progress");

        let bar = progress.find(".bar");
        let percent = progress.find(".percent");

        form.ajaxForm({
            beforeSend: function () {
                var percentVal = '0%';
                bar.width(percentVal)
                percent.html(percentVal);
                controls.attr("working", "1");
            },
            uploadProgress: function (event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            error: function (xhr, textStatus, errorThrown) {
                showAlert("Error processing upload: " + errorThrown);
            },
            success: function (contents, textStatus, xhr) {
                let percentVal = '100%';
                bar.width(percentVal)
                percent.html(percentVal);
                controls.removeAttr("working");

                try {

                    if (textStatus != "success") throw textStatus;

                    var result = JSON && JSON.parse(contents) || $.parseJSON(contents);
                    if (result.status != "OK") throw result.message;

                    this.processResult(result);

                } catch (err) {
                    var emsg = (err.message ? err.message : err);
                    console.log("JSONRequest Processing Error:" + emsg);
                    console.log(contents);
                    showAlert(emsg);
                }

            }.bind(this),
            complete: function (xhr, textStatus) {
                //form.attr("action", form.data("action"));

                this.resetFileInput();

            }.bind(this)
        });

        return form;
    }

    /**
     *
     * @param result
     */
    processResult(result) {
        let slots = this.component().find(".ArrayContents");

        if (result.contents) showAlert(result.contents);

        for (let a = 0; a < result.object_count; a++) {
            let current_object = result.objects[a];
            slots.append(current_object.html);
        }

        let instance = this;

        slots.find("[action='Remove']").each(function (index) {
            $(this).click(function (event) {
                instance.removeSlot($(this));
            });
        });

        $.event.trigger({
            type: "SessionUpload",
            message: "onProcessResult",
            time: new Date(),
            json_result: result
        });

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

        var uid = elm.parents(".Element").first().find("input[type='hidden']").first().attr("value");

        this.req.setFunction("remove");

        this.req.setParameter("uid", uid);

        this.req.onSuccess=function(request_result) {
            elm.parents(".Element").first().remove();
        };

        this.req.start();

    }
}
