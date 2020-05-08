function MCEImageBrowserDialog() {
    this.modal_pane = new ModalPopup();

    this.req = new JSONRequest();
    this.req.async = true;

    this.mce = null;

}

MCEImageBrowserDialog.prototype.onClickImage = function (imageID, event) {
    console.log("MCEImageBrowserDialog::onClickImage() imageID: " + imageID);

    var dialog = this;

    var url = "?ajax=1&cmd=mceImage&type=renderDimensionDialog&field_name=" + this.field_name + "&imageID=" + imageID;

    this.req.setURL(url);
    this.req.start(
        function (request_result) {

            var md = new MessageDialog();
            md.setText(request_result.json_result.contents);
            md.setCaption("Image Dimensions");
            md.icon_enabled = false;
            md.show();

            md.modal_pane.popup().find(".preview IMG").on("load", function (event) {
                md.modal_pane.centerContents();

            });

            var confirm_button = md.modal_pane.popup().find("button[action='confirm']");
            confirm_button.html("Insert");
            confirm_button.on("click", function (event) {

                var image_tag = $("<img src=''>");

                var form = md.modal_pane.popup().find("FORM");
                var render_mode = form.find("[name='render_mode']").val();

                var image_src = LOCAL + "/storage.php?cmd=" + render_mode + "&class=MCEImagesBean&id=" + imageID;

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
                    var popup_href = LOCAL + "/storage.php?cmd=image&class=MCEImagesBean&id=" + imageID;
                    final_tag = $("<a href='' class='image_popup'></a>");
                    final_tag.attr("href", popup_href);
                    final_tag.html(image_tag);

                }

                dialog.mce.editor.execCommand("mceInsertContent", false, final_tag.get(0).outerHTML);
                md.modal_pane.pane().remove();
                dialog.modal_pane.pane().remove();
                return false;
            });

            var cancel_button = confirm_button.clone();
            cancel_button.html("Cancel");
            cancel_button.attr("action", "cancel");

            cancel_button.insertAfter(confirm_button);
            cancel_button.on("click", function (event) {
                md.modal_pane.pane().remove();
                return false;
            });


        }.bind(this)
    );
}
MCEImageBrowserDialog.prototype.onClickRemove = function (imageID, event) {

    var url = "?ajax=1&cmd=mceImage&type=remove&field_name=" + this.field_name + "&imageID=" + imageID;

    this.req.setURL(url);
    this.req.start(
        function (request_result) {

            var element = this.modal_pane.popup().find(".ImageStorage .Contents .Element[imageID='" + imageID + "']");
            element.remove();
            this.modal_pane.centerContents();

        }.bind(this)
    );
}

MCEImageBrowserDialog.prototype.processImageResult = function (result) {
    var modal_pane = this.modal_pane;
    var dialog = this;

    for (var a = 0; a < result.result_count; a++) {
        var image = result.objects[a];
        modal_pane.popup().find(".ImageStorage .Contents").first().append(image.html);
    }

    modal_pane.popup().find(".ImageStorage .Contents .Element").each(function () {

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

MCEImageBrowserDialog.prototype.loadImages = function (imageID) {
    var modal_pane = this.modal_pane;

    var url = "?ajax=1&cmd=mceImage&type=find&field_name=" + this.field_name;

    if (imageID > 0) {
        url += "&imageID=" + imageID;
    } else {
        modal_pane.popup().find(".ImageStorage .Contents").first().empty();
    }

    this.req.setURL(url);
    this.req.start(
        function (request_result) {
            this.processImageResult(request_result.json_result);

        }.bind(this)
    );

}
MCEImageBrowserDialog.prototype.show = function (textarea) {
    this.mce_textarea = textarea;

    var modal_pane = this.modal_pane;

    modal_pane.showID("mceImage_browser");

    modal_pane.popup().find("[action='cancel']").click(function (event) {

        modal_pane.pane().remove();

    });

    modal_pane.paneClicked = function (event) {

    }

    var field = modal_pane.popup().find(".SessionUpload").first();
    this.field_name = field.attr("field_name");

    var upload_control = field.data("upload_control");

    upload_control.processResult = function (result) {

        for (var a = 0; a < result.result_count; a++) {
            var image = result.objects[a];
            var imageID = image.imageID;
            this.loadImages(imageID);
        }

    }.bind(this);

    this.loadImages();

    return false;
}
