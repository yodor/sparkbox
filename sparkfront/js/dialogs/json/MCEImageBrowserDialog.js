class InsertImageDialog extends ConfirmMessageDialog {

    constructor() {
        super("ConfirmMessageDialog");
        this.setType(MessageDialog.TYPE_PLAIN);

        this.parent = null;
        this.itemClass = "MCEImagesBean";
    }

    setImageID(imageID) {
        this.imageID = imageID;
    }

    setParent(dialog) {
        this.parent = dialog;
    }

    setContents(contents) {
        this.setText(contents);
    }

    buttonAction(action) {

        if (action == "confirm") {
            this.confirm();
        } else if (action == "cancel") {
            this.remove();
        }

    }

    confirm() {

        let form = this.element.querySelector("FORM");

        let width = parseInt(form.width.value);
        let height = parseInt(form.height.value);

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

        let image_url = new URL(STORAGE_LOCAL, location.href);
        image_url.searchParams.set("cmd", "image");
        image_url.searchParams.set("class", this.itemClass);
        image_url.searchParams.set("id", this.imageID);

        const image_tag = document.createElement("IMG");

        if (form.render_mode.value == "fit_prc") {
            if (width>0) {
                image_tag.setAttribute("width", "" + width + "%");
            }
            if (height>0) {
                image_tag.setAttribute("height", "" + height + "%");
            }
        }
        else if (form.render_mode.value == "fit_px") {
            image_url.searchParams.set("width", ""+width);
            image_url.searchParams.set("height", ""+height);
        }

        image_tag.setAttribute("src", image_url.href);
        image_tag.setAttribute("alt", form.caption.value);

        let html = image_tag.outerHTML;

        if (form.enable_popup.checked) {
            const popup = document.createElement("A");
            popup.setAttribute("class", "ImagePopup");
            popup.setAttribute("itemID", this.imageID);
            popup.setAttribute("itemClass", this.itemClass);
            popup.setAttribute("title", form.caption.value);
            popup.appendChild(image_tag);
            html = popup.outerHTML;
        }

        this.parent.mce_textarea.editor.execCommand("mceInsertContent", false, html);

        this.remove();
        this.parent.remove();
    }

}

class MCEImageBrowserDialog extends JSONDialog {

    constructor() {
        super();

        this.mce_textarea = null;

        this.insert_image = new InsertImageDialog();
        this.insert_image.setTitle("Insert Image");
        this.insert_image.setParent(this);

        //SessionUpload control object is not assigned yet
        this.uploadInput = this.element.querySelector(".SessionUpload");

        this.req.setResponder("mceImage");
        this.req.setParameter("field_name", this.uploadInput.getAttribute("field"));

        this.collection = this.element.querySelector(".ImageStorage");
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

            let element = this.element.querySelector("[imageID='" + imageID + "']");
            element.remove();


        } else if (funct == "find") {

            for (let a = 0; a < jsonResult.object_count; a++) {
                let object_result = jsonResult.objects[a];
                this.appendResult(object_result);
            }

        }

    }

    /**
     * Process single elements from the objects returned
     * Append html to the viewport and assign onclick/remove event handlers
     * @param object_result
     */
    appendResult(object_result) {

        const resultElement = document.templateFactory.nodeList(object_result.html)[0];

        const imageID = resultElement.getAttribute("imageID");

        const dialog = this;

        let image_contents = resultElement.querySelector(".image_contents");
        image_contents.addEventListener("click", (event)=>this.onClickImage(imageID));

        let remove_button = resultElement.querySelector(".remove_button");
        remove_button.addEventListener("click", (event)=>this.removeImage(imageID));

        this.collection.append(resultElement);

    }

    show() {

        super.show();

        const sessionUpload = this.uploadInput.upload_control;
        sessionUpload.processResult = this.processUploadResult.bind(this);

        this.req.setFunction("find");
        this.req.start();

    }

    /**
     *
     * @param request_result {JSONRequestResult}
     */
    processUploadResult(request_result) {

        let result = request_result.json_result;
        console.log("Result count: " + result.object_count);

        for (let a = 0; a < result.object_count; a++) {
            //object_result (imageID=>beanID, html=>html)
            let object_result = result.objects[a];
            this.appendResult(object_result);
        }

    }


    onClickImage(imageID) {

        this.req.setFunction("renderDimensionDialog");
        this.req.setParameter("imageID", imageID);
        this.req.start();
    }

    removeImage(imageID) {

        this.req.setFunction("remove");
        this.req.setParameter("imageID", imageID);
        this.req.start();

    }

}
