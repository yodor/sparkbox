class SessionUpload extends Component {

    //UploadControlResponder function calls
    static FUNCTION_UPLOAD = "upload";
    static FUNCTION_REMOVE = "remove";

    constructor() {
        super();
        this.setClass(".SessionUpload");

        this.request = new JSONRequest();

        const observer = this.onRequestEvent.bind(this);
        this.request.addObserver(observer);

        this.controls = null;
        this.slots = null;
        this.fileInput = null;
        this.max_slots = 0;
    }

    setField(field) {
        super.setField(field);
        this.request.setParameter("field_name", field);
    }

    initialize() {

        super.initialize();

        this.element.upload_control = this;
        this.request.setResponder(this.element.getAttribute(JSONRequest.KEY_RESPONDER));

        this.controls = this.element.querySelector(".Controls");

        this.slots = this.element.querySelector(".ArrayContents");

        this.fileInput = this.element.querySelector("INPUT[type='file']");
        this.fileInput.addEventListener("change", (event)=>this.uploadFileChanged(event));

        this.max_slots = this.fileInput.getAttribute("max_slots");

        this.slots.querySelectorAll("[action='Remove']").forEach((element)=>{
            element.addEventListener("click", (event)=>this.removeSlot(element));
        });

    }


    /**
     * JSONRequest event observer
     * @param event {SparkEvent}
     */
    onRequestEvent(event) {

        if (event.isEvent(JSONRequest.EVENT_STARTED)) {
            this.controls.setAttribute("working", "");
            this.updateProgress(0);
        }
        else if (event.isEvent(JSONRequest.EVENT_PROGRESS)) {
            this.updateProgress(event.percent);
        }
        else if (event.isEvent(JSONRequest.EVENT_FINISHED)) {
            this.controls.removeAttribute("working");
            this.request.clearPost();
        }

    }

    updateProgress(value)
    {
        let percentVal = parseInt(value)+"%";

        this.controls.querySelector(".bar").style.width = percentVal;
        this.controls.querySelector(".percent").innerText = percentVal;
    }

    uploadFileChanged(event) {

        //console.log("SessionUpload::uploadFileChanged()");
        let upload_count = this.fileInput.files.length;
        if (upload_count<1) {
            //console.log("No file selected for upload");
            return;
        }
        //console.log("Upload count: " + upload_count);

        //process current number of slots
        let active_slots = this.slots.children.length;

        if (active_slots >= this.max_slots) {
            showAlert("Maximum number of upload slots reached");
            this.fileInput.value="";
            return;
        }

        if (active_slots + upload_count > this.max_slots) {
            showAlert("Select less files");
            this.fileInput.value="";
            return;
        }

        this.request.setFunction(SessionUpload.FUNCTION_UPLOAD);

        let fileData = new FormData();
        for (let a=0;a<this.fileInput.files.length;a++) {
            fileData.append(this.fileInput.getAttribute("name"), this.fileInput.files[a]);
        }
        this.request.setPostFormData(fileData);

        this.request.onSuccess = this.uploadSuccess.bind(this);
        this.request.start();
    }


    /**
     *
     * @param result {JSONRequestResult}
     */
    uploadSuccess(result) {

        this.fileInput.value = "";

        const response = result.response;

        if (response.contents) showAlert(response.contents);

        console.log(result);

        for (let a = 0; a < response.object_count; a++) {
            let current_object = response.objects[a];

            //.Element
            const elementLoaded = document.templateFactory.nodeList(current_object.html)[0];
            this.slots.appendChild(elementLoaded);

            elementLoaded.querySelector("[action='Remove']").addEventListener("click", (event)=>this.removeSlot(elementLoaded));
        }

        const updateEvent = new SparkEvent(SparkEvent.DOM_UPDATED);
        updateEvent.source = this.slots;
        document.dispatchEvent(updateEvent);

    }


    /**
     *
     * @param remove_button {Element}
     */
    removeSlot(remove_button) {

        const item = remove_button.closest(".Element");
        const uid = item.querySelector("INPUT[type='hidden']").value;
        //console.log(`Removing UID: ${uid}`);

        this.request.onSuccess = function(result) {
            item.remove();
            document.tooltip.hide();
        };

        this.request.setFunction(SessionUpload.FUNCTION_REMOVE);
        this.request.setParameter("uid", uid);
        this.request.start();

    }
}
