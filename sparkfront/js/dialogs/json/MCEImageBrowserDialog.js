class MCEImageBrowserDialog extends JSONDialog {

    constructor() {
        super();
        this.setID("mceImage_browser");
        this.mce_textarea = null;

        this.field_name = null;

        this.req.setResponder("mceImage");
    }

    setMCETextArea(textarea) {
        this.mce_textarea = textarea;
    }

    buttonAction(action, dialog) {

        this.remove();

    }

    show() {

        super.show();

        //subsequent shows need clear parameters
        this.req.clearParameters();

        var field = this.modal_pane.popup.find(".SessionUpload").first();

        this.field_name = field.attr("field");

        this.req.setParameter("field_name", this.field_name);

        var upload_control = field.data("upload_control");

        upload_control.processResult = function (result) {

            for (var a = 0; a < result.result_count; a++) {
                var image = result.objects[a];
                var imageID = image.imageID;
                this.loadImages(imageID);
            }

        }.bind(this);

        this.loadImages();

    }

    onClickImage(imageID, event) {

        console.log("MCEImageBrowserDialog::onClickImage() imageID: " + imageID);

        var dialog = this;

        this.req.setFunction("renderDimensionDialog");
        this.req.setParameter("imageID", imageID);

        this.req.onSuccess = function(request_result) {

                var md = new MessageDialog();
                md.setText(request_result.json_result.contents);
                md.setCaption("Image Dimensions");
                md.icon_enabled = false;
                md.show();

                md.modal_pane.popup.find(".preview IMG").on("load", function (event) {
                    md.modal_pane.centerContents();
                });

                var confirm_button = md.modal_pane.popup.find("button[action='confirm']");
                confirm_button.html("Insert");
                confirm_button.on("click", function (event) {

                    var image_tag = $("<img src=''>");

                    var form = md.modal_pane.popup.find("FORM");
                    var render_mode = form.find("[name='render_mode']").val();

                    var image_src = STORAGE_LOCAL + "?cmd=" + render_mode + "&class=MCEImagesBean&id=" + imageID;

                    var width = parseInt(form.find("[name='width']").val());
                    var height = parseInt(form.find("[name='height']").val());

                    var popup = form.find("[name='enable_popup']");

                    if (width < 1 && height < 1) {
                        if (render_mode != "gallery_photo") {
                            showAlert("Crop and Thumbnail modes require atleast one of width or height values greater than zero.");
                            return;
                        }
                    }
                    if (isNaN(width) || width < 1) {
                        width = -1;
                    } else {
                        image_tag.attr("width", width);
                    }
                    if (isNaN(height) || height < 1) {
                        height = -1;
                    } else {
                        image_tag.attr("height", height);
                    }
                    image_src += "&width=" + width + "&height=" + height;

                    var caption = form.find("[name='caption']").val();
                    if (caption.length > 0) {
                        image_tag.attr("alt", caption);
                    }
                    image_tag.attr("src", image_src);

                    var final_tag = image_tag;

                    if (popup.is(":checked")) {

                        let url = new URL(STORAGE_LOCAL, location.href);
                        url.searchParams.set("cmd", "image");
                        url.searchParams.set("class", "MCEImagesBean");
                        url.searchParams.set("id", imageID);

                        final_tag = $("<a href='' class='ImagePopup'></a>");
                        final_tag.attr("href", url.href);
                        final_tag.html(image_tag);

                    }

                    dialog.mce_textarea.editor.execCommand("mceInsertContent", false, final_tag.get(0).outerHTML);
                    md.remove();
                    dialog.remove();
                    return false;
                });

                var cancel_button = confirm_button.clone();
                cancel_button.html("Cancel");
                cancel_button.attr("action", "cancel");

                cancel_button.insertAfter(confirm_button);
                cancel_button.on("click", function (event) {
                    md.remove();
                    return false;
                });



        }.bind(this);

        this.req.start();
    }

    onClickRemove(imageID, event) {

        this.req.setFunction("remove");
        this.req.setParameter("imageID", imageID);

        this.req.onSuccess = function (request_result) {
            let element = this.modal_pane.popup.find(".ImageStorage .Collection .Element[imageID='" + imageID + "']");
            element.remove();
            this.modal_pane.centerContents();
        }.bind(this);

        this.req.start();
    }

    processImageResult(result) {

        var modal_pane = this.modal_pane;
        var dialog = this;

        for (var a = 0; a < result.result_count; a++) {
            var image = result.objects[a];
            this.modal_pane.popup.find(".ImageStorage .Collection").first().append(image.html);
        }

        this.modal_pane.popup.find(".ImageStorage .Collection .Element").each(function () {

            var imageID = $(this).attr("imageID");

            $(this).on("click", function (event) {

                this.onClickImage(imageID, event);

            }.bind(dialog));

            var img = $(this).children("IMG").first();
            img.on("load", function (event, num_loaded) {
                this.centerContents();
            }.bind(modal_pane));

            var remove_button = $(this).children(".remove_button").first();
            remove_button.on("click", function (event) {
                dialog.onClickRemove(imageID, event);
                return false;
            });

        });
    }

    loadImages(imageID) {

        this.req.setFunction("find");

        if (imageID > 0) {
            this.req.setParameter("imageID", imageID);
        } else {
            this.modal_pane.popup.find(".ImageStorage .Collection").first().empty();
        }

        this.req.onSuccess = function (request_result) {
            this.processImageResult(request_result.json_result);
        }.bind(this);

        this.req.start();

    }

}
