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

        if (action === "confirm") {
            this.confirm();
        } else if (action === "cancel") {
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

        if (form.render_mode.value === "fit_prc") {
            if (width>0) {
                image_tag.setAttribute("width", "" + width + "%");
            }
            if (height>0) {
                image_tag.setAttribute("height", "" + height + "%");
            }
        }
        else if (form.render_mode.value === "fit_px") {
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
        this.collection = this.element.querySelector(".ImageStorage");

        this.request.setParameter("field_name", this.uploadInput.getAttribute("field"));
    }

    setMCETextArea(textarea) {
        this.mce_textarea = textarea;
    }

    buttonAction(action, dialog) {
        this.remove();
    }

    processResult(responder, funct, result) {

        let response = result.response;
        let imageID = this.request.getParameter("imageID");

        if (funct === "renderDimensionDialog") {

            this.insert_image.setContents(response.contents);
            this.insert_image.setImageID(this.request.getParameter("imageID"));
            this.insert_image.show();

        } else if (funct === "remove") {

            let element = this.collection.querySelector("[imageID='" + imageID + "']");
            element.remove();

        } else if (funct === "find") {

            this.collection.innerHTML = "";
            this.appendResponseItems(response);

        }

    }

    /**
     *
     * @param result {JSONRequestResult}
     */
    processUploadResult(result) {
        this.appendResponseItems(result.response);
    }

    appendResponseItems(response) {
        for (let a = 0; a < response.object_count; a++) {
            const object_result = response.objects[a];
            const resultElement = document.templateFactory.nodeList(object_result.html)[0];
            this.appendElement(resultElement);
        }

        const event = new SparkEvent(SparkEvent.DOM_UPDATED);
        event.source = this.collection;
        document.dispatchEvent(event);
    }
    /**
     * Append the collection with 'element' and assign onclick handlers - removeImage and onClickImage
     * Append html to the viewport and assign onclick/remove event handlers
     * @param element {HTMLElement}
     */
    appendElement(element) {

        const imageID = element.getAttribute("imageID");

        let image_element = element.querySelector(".image_contents");
        image_element.addEventListener("click", (event)=>{
            this.request.setFunction("renderDimensionDialog");
            this.request.setParameter("imageID", imageID);
            this.request.start();
        });

        let remove_button = element.querySelector(".remove_button");
        remove_button.addEventListener("click", (event)=> {

            this.request.setFunction("remove");
            this.request.setParameter("imageID", imageID);
            this.request.start();

        });

        this.collection.append(element);

    }

    show() {

        super.show();

        const sessionUpload = this.uploadInput.upload_control;
        //redirect SessionUpload
        sessionUpload.uploadSuccess = this.processUploadResult.bind(this);

        this.request.setFunction("find");
        //cleanup
        this.request.removeParameter("imageID");
        this.request.start();

    }



}