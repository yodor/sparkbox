class ZoomData {
    constructor() {
        /**
         * MouseDown/TouchStart - MouseUp/TouchEnd
         */
        this.down = false;
        /**
         * Current background position
         */
        this.position = {x:0, y:0};
        /**
         * Current scaled image dimensions
         */
        this.scaled = {width: 0, height:0};
        /**
         * Original image dimensions
         */
        this.original = {width: 0, height:0};
    }

}
class ImagePopup extends SparkObject {

    static EVENT_POSITION_NEXT = "position_next";
    static EVENT_POSITION_PREV = "position_prev";
    static EVENT_FETCH_COMPLETE = "fetch_complete";
    static EVENT_CLOSED = "closed";

    constructor() {
        super();

        this.pos = -1;
        /**
         *
         * @type {NodeList}
         */
        this.collection = null;

        this.relation = "";

        /**
         *
         * @type {HTMLElement}
         */
        this.popup = document.templateFactory.nodeList(this.createPopupContents())[0];
        //assign listeners to buttons of popup contents
        if (!(this.popup instanceof HTMLElement))  {
            showAlert("Incorrect popup contents");
            throw new Error("Incorrect popup contents");
        }

        this.contents = this.popup.querySelector(".Contents");

        this.buttonNext = this.popup.querySelector("[action='NextImage']");
        this.buttonPrev = this.popup.querySelector("[action='PrevImage']");
        this.buttonZoom = this.popup.querySelector("[action='ZoomImage']");
        this.buttonClose = this.popup.querySelector("[action='CloseImage']");

        this.buttonNext.addEventListener("click", (event)=>this.nextImage());
        this.buttonPrev.addEventListener("click", (event)=>this.prevImage());
        this.buttonZoom.addEventListener("click", (event)=>this.zoomImage());
        this.buttonClose.addEventListener("click", (event)=>this.closeImage());

        /**
         * Zoom data
         * @type {ZoomData}
         */
        this.zoom = new ZoomData();

        /**
         * Working image object
         * @type {HTMLImageElement}
         */
        this.image = new Image();
        this.image.addEventListener("load", (event)=>{
            this.popup.style.backgroundImage = "url("+this.image.src+")";
            this.popup.removeAttribute("loading");

        });

        this.popup.addEventListener("mousedown", (event)=> this.mouseDownHandler(event));
        this.popup.addEventListener("touchstart", (event)=> this.mouseDownHandler(event));

        this.popup.addEventListener("mouseup", (event) => this.mouseUpHandler(event));
        this.popup.addEventListener("touchend", (event) => this.mouseUpHandler(event));

        this.popup.addEventListener("mousemove", (event) => this.mouseMoveHandler(event));
        this.popup.addEventListener("touchmove", (event) => this.mouseMoveHandler(event));

        window.addEventListener("load", (event) => this.assignListeners(document));
        window.addEventListener("resize", (event)=> this.setupZoom());

        document.addEventListener(SparkEvent.DOM_UPDATED, (event) => this.assignListeners(event.source));

    }

    nextImage() {

        this.pos++;
        if (this.pos >= this.collection.length) this.pos = 0;

        this.fetchImage();

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_POSITION_NEXT, this);
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

    }

    prevImage() {

        this.pos--;
        if (this.pos < 0) this.pos = this.collection.length - 1;

        this.fetchImage();

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_POSITION_PREV,this);
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

    }

    /**
     *  Return the html to be shown inside a ModalPane
     * @returns {string}
     */
    createPopupContents() {
        let html = "";
        html += "<div class='ImagePopupPane'>";
        html += "<div class='Header'><a class='Button' action='ZoomImage'></a><a class='Button' action='CloseImage' default_action></a></div>";
        html += "<div class='Contents'>";
        html += "<a class='Button' action='PrevImage'></a><a class='Button' action='NextImage'></a>";
        html += "</div>";
        html += "<div class='Footer'></div>";
        html += "</div>";
        return html;
    }

    /**
     * Show the ImageStorage object as popup. itemID and itemClass attributes are parsed to show the actual image
     * related images are loaded into collection
     * @param element {HTMLElement}
     */
    show(element) {

        const itemClass = element.getAttribute("itemClass");
        const itemID = element.getAttribute("itemID");

        if (!itemClass || !itemID) {
            showAlert("itemClass and itemID attributes required");
            return;
        }

        this.popup.setAttribute("loading","");

        const relation = element.getAttribute("relation");
        const list = element.getAttribute("list-relation");

        let collection_selector = "";

        if (list) {
            this.relation = list;
            collection_selector = "relation='" + list + "'";
        }
        //select all tags having attribute = 'relation'
        else if (relation) {
            this.relation = relation;
            collection_selector = "relation='" + relation + "'";
        }
        else {
            this.relation = itemClass;
            collection_selector  = "itemClass='" + itemClass + "'";
        }

        this.collection = document.querySelectorAll("[" + collection_selector + "]");

        this.pos = 0;

        for (let a= 0; a < this.collection.length; a++) {
            const item = this.collection.item(a);

            if (item.getAttribute("itemID") === itemID) {
                this.pos = a;
                break;
            }
        }

        document.body.appendChild(this.popup);
        this.fetchImage();

    }

    fetchImage() {

        this.popup.setAttribute("loading","");

        let current = this.collection[this.pos];

        if (!current) {
            showAlert("Requested position: " + this.pos + " out of bounds for this image collection.");
            this.closeImage();
            return;
        }

        const itemClass = current.getAttribute("itemClass");
        const itemID = current.getAttribute("itemID");

        let url = new URL(STORAGE_LOCAL, location.href);
        url.searchParams.set("cmd", "image");
        url.searchParams.set("class", itemClass);
        url.searchParams.set("id", itemID);


        let caption = current.getAttribute("title");

        let footer = this.popup.querySelector(".Footer");
        if (caption) {
            footer.innerHTML = "<h1 class='Caption'>" + caption + "</h1>";
        }
        else {
            footer.innerHTML = "";
        }

        //trigger on load
        this.image.src = url.href;

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_FETCH_COMPLETE, this)
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

    }

    closeImage() {

        this.image.src="";
        this.popup.style.backgroundImage = "";
        this.popup.style.backgroundPosition = "";

        this.popup.removeAttribute("loading");
        this.popup.removeAttribute("zoom");
        this.popup.remove();

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_CLOSED,this);
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

    }

    mouseDownHandler(event)
    {
        if (!this.popup.hasAttribute("zoom")) return;
        this.zoom.down = true;
    }
    mouseUpHandler(event)
    {
        if (!this.popup.hasAttribute("zoom")) return;
        this.zoom.down = false;
    }
    mouseMoveHandler(event)
    {
        if (!this.popup.hasAttribute("zoom")) return;
        if (!this.zoom.down) return false;

        let mX = event.clientX;
        if (event.clientX == null) {
            mX = event.touches[0].clientX;
        }
        if (mX<0)mX=0;

        if (mX>this.popup.clientWidth) {
            mX = this.popup.clientWidth;
        }

        let mY = event.clientY;
        if (event.clientY == null) {
            mY = event.touches[0].clientY;
        }
        if (mY<0)mY=0;
        if (mY>this.popup.clientHeight) {
            mY = this.popup.clientHeight;
        }


        if (this.zoom.scaled.width > this.popup.clientWidth) {
            let dX = (this.zoom.scaled.width - this.popup.clientWidth) / this.popup.clientWidth;
            this.zoom.position.x = (dX * mX * -1);
        }

        if (this.zoom.scaled.height > this.popup.clientHeight) {
            let dY = (this.zoom.scaled.height - this.popup.clientHeight) / this.popup.clientHeight;
            this.zoom.position.y = (dY * mY * -1);
        }

        this.popup.style.backgroundPositionX = this.zoom.position.x + "px";
        this.popup.style.backgroundPositionY = this.zoom.position.y + "px";

    }
    zoomImage() {

        if (this.popup.hasAttribute("zoom")) {
            this.popup.removeAttribute("zoom");

            this.popup.style.backgroundPosition = "center center";

            return;
        }

        this.popup.setAttribute("zoom", "");
        this.setupZoom();

    }

    setupZoom()
    {
        if (!this.popup.hasAttribute("zoom"))return;

        //console.log(`Viewport [${this.popup.clientWidth}x${this.popup.clientHeight}]`);
        //console.log(`Image [${this.image.width}x${this.image.height}]`);

        const ratio = Math.max(this.popup.clientWidth / this.image.width, this.popup.clientHeight / this.image.height);
        const scaledWidth = Math.round(this.image.width * ratio);
        const scaledHeight = Math.round(this.image.height * ratio);

        //console.log(`Scaled [${scaledWidth}x${scaledHeight}]`);

        this.zoom.position.x = -(scaledWidth - this.popup.clientWidth)/2;
        this.zoom.position.y = -(scaledHeight - this.popup.clientHeight)/2;

        this.zoom.scaled.width = scaledWidth;
        this.zoom.scaled.height = scaledHeight;
        this.zoom.scaled.ratio = ratio;

        this.popup.style.backgroundPositionX = this.zoom.position.x + "px";
        this.popup.style.backgroundPositionY = this.zoom.position.y + "px";
    }

    assignListeners(parentNode) {

        parentNode.querySelectorAll(".ImagePopup:not([href])").forEach( (element) => {

            element.addEventListener("click", (event)=>document.imagePopup.show(element,event));

        });
    }
}

document.imagePopup = new ImagePopup();
