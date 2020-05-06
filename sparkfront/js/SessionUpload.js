function SessionUpload() {
    this.field = null;
    this.field_name = "";

}

SessionUpload.prototype.attachWith = function (name) {

    console.log("SessionUpload::attachWith() field_name=[" + name + "]");

    this.field = $("body").find(".SessionUpload[field='" + name + "']").first();

    this.field_name = name;

    this.field.data("upload_control", this);

    var file_input = this.field.find("input[type='file']").first();

    file_input.change(function (event) {
        this.uploadFileChanged();
    }.bind(this));

    var instance = this;

    var slots = this.field.find(".ArrayContents");
    slots.find("[action='Remove']").each(function (index) {
        $(this).click(function (event) {
            instance.removeSlot($(this));
        });

    })
}

SessionUpload.prototype.uploadFileChanged = function (event) {
    console.log("SessionUpload::uploadFileChanged()");

    var file_input = this.field.find("input[type='file']").first();

    var max_slots = file_input.attr("max_slots");
    var validator = file_input.attr("validator");

    var slots = this.field.find(".ArrayContents");
    var controls = this.field.find(".Controls");
    var form = this.field.parents("FORM").first();

//   console.log(form);

    //process current number of slots
    var active_slots = slots.children().length;
    if (active_slots >= max_slots) {

        showAlert("Maximum number of upload slots reached");
        this.resetFileInput();
        return;
    }
    if (file_input.get(0).files) {

        var upload_count = file_input.get(0).files.length;
        if (active_slots + upload_count > max_slots) {
            showAlert("Select less files");
            this.resetFileInput();
            return;
        }
    }


    this.prepareUploadForm();

    var current_get = window.location.search.substring(1);
    var form_url = "?" + current_get + "&ajax=1&cmd=" + this.field.attr("handler_command") + "&type=upload&field_name=" + this.field_name + "&validator=" + validator;


    form.attr("action", form_url);

    var submit = form.attr("onSubmit");
    form.attr("onSubmit", "return true");
    form.submit();

    console.log("Submit Form to URL: " + form_url);

    form.unbind('submit').find('input:submit,input:image,button:submit').unbind('click');
    form.attr("onSubmit", submit);
}

SessionUpload.prototype.prepareUploadForm = function () {
    var controls = this.field.find(".Controls");
    var form = this.field.parents("FORM").first();

    var action_stored = form.data("action_stored");
    if (!action_stored) {
        form.data("action_stored", 1);
        var action = form.attr("action");
        if (!action) action = "";
        form.data("action", action);
    }

    var bar = controls.find(".bar");
    var percent = controls.find(".percent");

    form.ajaxForm({
        beforeSend: function () {
            var percentVal = '0%';
            bar.width(percentVal)
            percent.html(percentVal);
            controls.attr("class", "Controls working");
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
            var percentVal = '100%';
            bar.width(percentVal)
            percent.html(percentVal);
            controls.attr("class", "Controls");

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
SessionUpload.prototype.processResult = function (result) {
    var slots = this.field.find(".ArrayContents");

    if (result.contents) showAlert(result.contents);

    for (var a = 0; a < result.object_count; a++) {
        var current_object = result.objects[a];
        slots.append(current_object.html);
    }

    var instance = this;

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
SessionUpload.prototype.resetFileInput = function () {

    var file_input = this.field.find("input[type='file']").first();

    file_input.wrap("<form>").closest("form").get(0).reset();
    file_input.unwrap();


    //file_input.replaceWith(file_input.clone(true));
    console.log("Upload control reset");
}

SessionUpload.prototype.removeSlot = function (elm) {

    var uid = elm.parents(".Element").first().find("input[type='hidden']").first().attr("value");

    var req = new JSONRequest();
    req.appendURL("ajax=1&cmd=" + this.field.attr("handler_command") + "&type=remove&field_name=" + this.field_name + "&uid=" + uid);

    req.start(
        function (request_result) {
            elm.parents(".Element").first().remove();
        }
    );

}
