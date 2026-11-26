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
    static EVENT_OPENED = "opened";

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

        //the focus before the popup opened - it goes back there when the popup closes
        this.returnFocus = null;
        //the swipe of a finger (not zoomed): its start
        this.swipeStart = null;
        this.onKeyDown = this.keyDownHandler.bind(this);

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
        //a photo that does not load - no endless spinner
        this.image.addEventListener("error", (event)=>{
            if (this.image.getAttribute("src")) this.popup.removeAttribute("loading");
        });

        this.popup.addEventListener("mousedown", (event)=> this.mouseDownHandler(event));
        this.popup.addEventListener("touchstart", (event)=> this.mouseDownHandler(event), {passive: true});
        this.popup.addEventListener("touchstart", (event)=> this.swipeStartHandler(event), {passive: true});

        this.popup.addEventListener("mouseup", (event) => this.mouseUpHandler(event));
        this.popup.addEventListener("touchend", (event) => this.mouseUpHandler(event));
        this.popup.addEventListener("touchend", (event) => this.swipeEndHandler(event));

        this.popup.addEventListener("mousemove", (event) => this.mouseMoveHandler(event));
        this.popup.addEventListener("touchmove", (event) => this.mouseMoveHandler(event), {passive: true});

        //the popups of the page (the script is deferred - the page may be parsed already)
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", (event) => this.assignListeners(document));
        }
        else {
            this.assignListeners(document);
        }
        window.addEventListener("resize", (event)=> this.setupZoom());

        document.addEventListener(SparkEvent.DOM_UPDATED, (event) => this.assignListeners(event.source));

        this.enabled = true;
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
     *  The html of the popup pane (its buttons - the photo is its background)
     * @returns {string}
     */
    createPopupContents() {
        //the texts of the buttons - the page gives their translations (ImagePopup.php - HTMLHead::addTexts)
        const label = (text) => ((typeof tr === "function") ? tr(text) : text).replace(/&/g, "&amp;").replace(/'/g, "&#39;").replace(/</g, "&lt;");
        let html = "";
        html += "<div class='ImagePopupPane' role='dialog' aria-modal='true'>";
        html += "<div class='Header'><button type='button' class='Button' action='ZoomImage' aria-label='" + label("Zoom") + "'></button>";
        html += "<button type='button' class='Button' action='CloseImage' default_action aria-label='" + label("Close") + "'></button></div>";
        html += "<div class='Contents'>";
        html += "<button type='button' class='Button nav-arrow prev' action='PrevImage' aria-label='" + label("Previous photo") + "'>&#10094;</button>";
        html += "<button type='button' class='Button nav-arrow next' action='NextImage' aria-label='" + label("Next photo") + "'>&#10095;</button>";
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

        if (!this.enabled)return;

        const itemClass = element.getAttribute("itemClass");
        const itemID = element.getAttribute("itemID");

        if (!itemClass || !itemID) {
            showAlert("itemClass and itemID attributes required");
            return;
        }

        this.returnFocus = document.activeElement;
        document.body.style.overflow = "hidden";

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

        //one photo: no arrows
        if (this.collection.length < 2) this.popup.setAttribute("single", "");
        else this.popup.removeAttribute("single");

        document.body.appendChild(this.popup);
        document.addEventListener("keydown", this.onKeyDown);
        this.buttonClose.focus();

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_OPENED, this)
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

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

        let url = new URL(document.Spark.STORAGE_LOCAL, location.href);
        url.searchParams.set("cmd", "image");
        url.searchParams.set("class", itemClass);
        url.searchParams.set("id", itemID);


        let caption = current.getAttribute("title");

        //the title as text (a title may have '<' - it is not HTML)
        let footer = this.popup.querySelector(".Footer");
        footer.innerHTML = "";
        if (caption) {
            const heading = document.createElement("h1");
            heading.className = "Caption";
            heading.textContent = caption;
            footer.appendChild(heading);
        }
        this.popup.setAttribute("aria-label", caption || "");

        //trigger on load
        this.image.src = url.href;

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_FETCH_COMPLETE, this)
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


    closeImage() {

        this.image.src="";
        this.popup.style.backgroundImage = "";
        this.popup.style.backgroundPosition = "";

        this.popup.removeAttribute("loading");
        this.popup.removeAttribute("zoom");
        this.popup.remove();
        document.removeEventListener("keydown", this.onKeyDown);

        //the overflow of the page as the page had it
        document.body.style.overflow = "";
        if (this.returnFocus instanceof HTMLElement && document.contains(this.returnFocus)) this.returnFocus.focus();
        this.returnFocus = null;

        const sparkEvent = new SparkEvent(ImagePopup.EVENT_CLOSED,this);
        this.notify(sparkEvent);
        document.dispatchEvent(sparkEvent);

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

    /**
     * Escape closes, the arrows show the previous / the next photo
     * @param event {KeyboardEvent}
     */
    keyDownHandler(event) {
        if (!this.popup.isConnected) return;
        if (event.key === "Escape") {
            event.preventDefault();
            if (this.popup.hasAttribute("zoom")) this.zoomImage();
            else this.closeImage();
        }
        else if (event.key === "ArrowRight" && !this.popup.hasAttribute("single")) {
            event.preventDefault();
            this.nextImage();
        }
        else if (event.key === "ArrowLeft" && !this.popup.hasAttribute("single")) {
            event.preventDefault();
            this.prevImage();
        }
    }

    swipeStartHandler(event) {
        if (this.popup.hasAttribute("zoom") || event.touches.length !== 1) {
            this.swipeStart = null;
            return;
        }
        this.swipeStart = {x: event.touches[0].clientX, y: event.touches[0].clientY};
    }

    /**
     * A horizontal swipe (not zoomed) shows the next / the previous photo
     */
    swipeEndHandler(event) {
        if (!this.swipeStart || this.popup.hasAttribute("single") || event.changedTouches.length < 1) return;
        const dx = event.changedTouches[0].clientX - this.swipeStart.x;
        const dy = event.changedTouches[0].clientY - this.swipeStart.y;
        this.swipeStart = null;
        if (Math.abs(dx) < 50 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
        if (dx < 0) this.nextImage();
        else this.prevImage();
    }

    /**
     * The popup photos of '$parentNode' open the popup - by click, or by Enter / Space (they are buttons)
     * @param parentNode {ParentNode}
     */
    assignListeners(parentNode) {

        if (!parentNode || typeof parentNode.querySelectorAll !== "function") return;
        parentNode.querySelectorAll(".ImagePopup:not([href])").forEach( (element) => {

            //once per element (the page and its updates may pass it again)
            if (element.hasAttribute("popup-ready")) return;
            element.setAttribute("popup-ready", "");
            if (!element.hasAttribute("tabindex")) element.setAttribute("tabindex", "0");
            if (!element.hasAttribute("role")) element.setAttribute("role", "button");

            element.addEventListener("click", (event)=>document.imagePopup.show(element));
            element.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " ") return;
                event.preventDefault();
                document.imagePopup.show(element);
            });

        });
    }
}

document.imagePopup = new ImagePopup();