class InsertImageDialog extends ConfirmMessageDialog {

    constructor() {
        super();
        this.contents = "";

        /**
         *
         * @type {MessageDialog}
         */
        this.parent = null;
        this.icon_enabled = false;

        this.itemClass = "MCEImagesBean";

    }

    setImageID(imageID) {
        this.imageID = imageID;
    }

    setParent(dialog) {
        this.parent = dialog;
    }

    setContents(contents) {
        this.contents = contents;
        this.setText(this.contents);
    }

    buttonAction(action) {

        if (action == "confirm") {
            this.confirm();
        } else if (action == "cancel") {
            this.remove();
        }

    }

    confirm() {

        let image_url = new URL(STORAGE_LOCAL, location.href);
        image_url.searchParams.set("cmd", "image");
        image_url.searchParams.set("class", this.itemClass);
        image_url.searchParams.set("id", this.imageID);

        let form = this.modal_pane.popup.find("FORM");

        let render_mode = form.find("[name='render_mode']").val();
        let caption = form.find("[name='caption']").val();
        let enable_popup = form.find("[name='enable_popup']");

        let width = parseInt(form.find("[name='width']").val());
        let height = parseInt(form.find("[name='height']").val());

        if (isNaN(width) || width < 1) {
            width = -1;
        }
        if (isNaN(height) || height < 1) {
            height = -1;
        }

        if (width < 1 && height < 1) {
            showAlert("One of width or height should be positive number");
            return;
        }

        let image_tag = $("<img>");

        if (render_mode == "fit_prc") {
            if (width>0) {
                image_tag.attr("width", "" + width + "%");
            }
            if (height>0) {
                image_tag.attr("height", "" + height + "%");
            }
        }
        else if (render_mode == "fit_px") {
            image_url.searchParams.set("width", width);
            image_url.searchParams.set("height", height);
        }


        image_tag.attr("src", image_url.href);
        image_tag.attr("alt", caption);

        let final_tag = image_tag;

        if (enable_popup.is(":checked")) {
            console.log("Enabling popup");
            final_tag = $("<a href='#'></a>");
            final_tag.attr("class", "ImagePopup");
            final_tag.attr("itemID", this.imageID);
            final_tag.attr("itemClass", this.itemClass);
            final_tag.attr("title", caption);
            final_tag.html(image_tag);
        }

        console.log("Inserting into MCE: " + final_tag.get(0).outerHTML);
        this.parent.mce_textarea.editor.execCommand("mceInsertContent", false, final_tag.get(0).outerHTML);

        this.remove();

        this.parent.remove();
    }

    show() {

        super.show();
        this.modal_pane.popup.find(".preview IMG").on("load", function (event) {
            this.modal_pane.centerContents();
        }.bind(this));

    }
}

class MCEImageBrowserDialog extends JSONDialog {

    constructor() {
        super();
        this.setID("mceImage_browser");
        this.mce_textarea = null;

        this.field_name = null;

        this.req.setResponder("mceImage");

        this.insert_image = new InsertImageDialog();
        this.insert_image.setCaption("Insert Image");
        this.insert_image.setParent(this);
    }

    setMCETextArea(textarea) {
        this.mce_textarea = textarea;
    }

    buttonAction(action, dialog) {

        this.remove();

    }

    processResult(responder, funct, result) {
        let jsonResult = result.json_result;
        let message = jsonResult.message;
        let imageID = this.req.getParameter("imageID");

        if (funct == "renderDimensionDialog") {
            this.insert_image.setContents(jsonResult.contents);
            this.insert_image.setImageID(this.req.getParameter("imageID"));
            this.insert_image.show();
        } else if (funct == "remove") {
            let element = this.modal_pane.popup.find(".ImageStorage .Collection .Element[imageID='" + imageID + "']");
            element.remove();
            this.modal_pane.centerContents();
        } else if (funct == "find") {

            const dialog = this;

            for (var a = 0; a < jsonResult.result_count; a++) {
                var image = jsonResult.objects[a];
                this.modal_pane.popup.find(".ImageStorage .Collection").first().append(image.html);
            }

            this.modal_pane.popup.find(".ImageStorage .Collection .Element").each(function (index) {

                let imageID = $(this).attr("imageID");

                $(this).on("click", function (event) {
                    dialog.onClickImage(imageID, event);
                    return false;
                });

                let remove_button = $(this).children(".remove_button").first();
                remove_button.on("click", function (event) {
                    dialog.removeImage(imageID, event);
                    return false;
                });

            }); //each image

        } //find
    }

    show() {

        super.show();

        //subsequent shows need clear parameters
        this.req.clearParameters();

        var field = this.modal_pane.popup.find(".SessionUpload").first();

        this.field_name = field.attr("field");

        this.req.setParameter("field_name", this.field_name);

        var upload_control = field.data("upload_control");

        upload_control.processResult = this.processUploadResult.bind(this);

        this.loadImages();

        //setTimeout(this.loadImages.bind(this), 100);

    }

    /**
     * Handle new image upload to collection
     * @param result
     */
    processUploadResult(result) {
        for (var a = 0; a < result.result_count; a++) {
            var image = result.objects[a];
            var imageID = image.imageID;
            //load the image into the view
            this.loadImages(imageID);
        }
    }

    loadImages(imageID) {

        this.req.setFunction("find");
        this.req.removeParameter("imageID");

        if (imageID > 0) {
            this.req.setParameter("imageID", imageID);
        } else {
            this.modal_pane.popup.find(".ImageStorage .Collection").first().empty();
        }

        this.req.start();

    }

    onClickImage(imageID, event) {

        this.req.setFunction("renderDimensionDialog");
        this.req.setParameter("imageID", imageID);
        this.req.start();
    }

    removeImage(imageID, event) {

        this.req.setFunction("remove");
        this.req.setParameter("imageID", imageID);
        this.req.start();

    }

}
