class SessionUpload extends Component {

    constructor() {
        super();
        this.setClass(".SessionUpload");

        this.req = new JSONRequest();
        //jquery wrap of the element
        this.element = null;
        //jquery wrap of the input[type='file']
        this.file_input = null;


    }

    initialize() {

        super.initialize();

        this.element = $(this.selector()).first();

        this.element.data("upload_control", this);

        this.file_input = this.element.find("INPUT[type='file']").first();

        this.file_input.change(function (event) {

            this.uploadFileChanged();

        }.bind(this));

        let instance = this;

        let slots = this.element.find(".ArrayContents");
        slots.find("[action='Remove']").each(function (index) {
            $(this).click(function (event) {
                instance.removeSlot($(this));
            });
        })

        let validator = this.file_input.attr("validator");

        this.req.setResponder(this.element.attr("handler_command"));
        if (validator) {
            this.req.setParameter("validator", validator);
        }
        this.req.setParameter("field_name", this.field);
    }

    uploadFileChanged(event) {

        console.log("SessionUpload::uploadFileChanged()");

        let max_slots = this.file_input.attr("max_slots");
        let validator = this.file_input.attr("validator");

        let slots = this.element.find(".ArrayContents");
        let controls = this.element.find(".Controls");
        let form = this.element.parents("FORM").first();

        //process current number of slots
        let active_slots = slots.children().length;
        if (active_slots >= max_slots) {

            showAlert("Maximum number of upload slots reached");
            this.resetFileInput();
            return;
        }
        if (this.file_input.get(0).files) {

            let upload_count = this.file_input.get(0).files.length;
            if (active_slots + upload_count > max_slots) {
                showAlert("Select less files");
                this.resetFileInput();
                return;
            }
        }


        this.prepareUploadForm();

        this.req.setFunction("upload");

        //copy the request url
        form.attr("action", this.req.getURL().href);

        let submit = form.attr("onSubmit");
        form.attr("onSubmit", "return true");
        form.submit();

        console.log("Submit Form to URL: " + form.attr("action"));

        form.unbind('submit').find('input:submit,input:image,button:submit').unbind('click');
        form.attr("onSubmit", submit);
    }

    prepareUploadForm() {

        let controls = this.element.find(".Controls");
        let form = this.element.parents("FORM").first();

        let action_stored = form.data("action_stored");
        if (!action_stored) {
            form.data("action_stored", 1);
            let action = form.attr("action");
            if (!action) action = "";
            form.data("action", action);
        }

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
                form.attr("action", form.data("action"));
                this.resetFileInput();

            }.bind(this)
        });

    }

    /**
     *
     * @param result
     */
    processResult(result) {
        let slots = this.element.find(".ArrayContents");

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

        this.file_input.wrap("<form>").closest("form").get(0).reset();
        this.file_input.unwrap();

        //file_input.replaceWith(file_input.clone(true));
        console.log("Upload control reset element INPUT[type='file']");
    }

    /**
     * jquery element
     * @param elm
     */
    removeSlot(elm) {

        var uid = elm.parents(".Element").first().find("input[type='hidden']").first().attr("value");

        this.req.setFunction("remove");

        this.req.setParameter("uid", uid);

        this.req.start(
            function (request_result) {
                elm.parents(".Element").first().remove();
            }
        );

    }
}
