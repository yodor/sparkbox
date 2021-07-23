class ImagePopup {

    constructor() {
        this.pos = -1;

        this.collection = [];

        this.modal_pane = new ModalPopup();
        this.modal_pane.fullscreen = true;

        this.modal_pane.paneClicked = this.onClickPane.bind(this);
        this.relation = "";
    }

    onClickImage(event) {
        let viewport = this.modal_pane.popup;
        let zoom_enabled = $(viewport).attr("zoom-enabled");
        if (zoom_enabled==1) return;

        this.remove();
    }

    onClickPane(event) {
        let viewport = this.modal_pane.popup;
        let zoom_enabled = $(viewport).attr("zoom-enabled");
        if (zoom_enabled==1) return;

        this.remove();

    }

    nextImage(event) {

        let old_position = this.pos;

        this.pos++;
        if (this.pos >= this.collection.length) this.pos = 0;
        this.showImage();

        $.event.trigger({
            type: "ImagePopup",
            message: "onNextImage",
            time: new Date(),
            relation: this.relation
        });

        return false;
    }

    prevImage(event) {

        let old_position = this.pos;

        this.pos--;
        if (this.pos < 0) this.pos = this.collection.length - 1;

        this.showImage();

        $.event.trigger({
            type: "ImagePopup",
            message: "onPrevImage",
            time: new Date(),
            relation: this.relation
        });

        return false;
    }

    /**
     * Show the ImageStorage object as popup. itemID and itemClass attributes are parsed to show the actual image
     * @param aelm {jQuery}
     */
    popupImage(aelm) {


        let itemClass = aelm.attr("itemClass");
        let itemID = aelm.attr("itemID");

        if (!itemClass || !itemID) {
            showAlert("itemClass or itemID attribute not found");
            return;
        }

        this.relation = itemClass;

        let collection_selector = ".ImagePopup[itemClass='" + itemClass + "']";

        let relation = aelm.attr("relation");
        if (relation) {
            collection_selector += "[relation='" + relation + "']";
            this.relation = relation;
        }

        this.collection = $(collection_selector).toArray();

        //remove duplicates
        let reduced = this.collection.reduce(function (item, e1) {
            var matches = item.filter(function (e2)
            { return $(e1).attr("itemid") == $(e2).attr("itemid")});
            if (matches.length == 0) {
                item.push(e1);
            }
            return item;
        }, []);

        this.collection = reduced;

        this.pos = 0;

        for (var a=0;a<this.collection.length;a++) {
            let item = this.collection[a];
            if ($(item).attr("itemid") == aelm.attr("itemid")) {
                this.pos = a;
                break;
            }
        }


        this.modal_pane.showContent($(this.createPopupContents()));

        let buttonNext = this.modal_pane.popup.find("[action='NextImage']");
        buttonNext.on("click", this.nextImage.bind(this));

        let buttonPrev = this.modal_pane.popup.find("[action='PrevImage']");
        buttonPrev.on("click", this.prevImage.bind(this));

        let buttonZoom = this.modal_pane.popup.find("[action='ZoomImage']");
        buttonZoom.on("click", this.zoomImage.bind(this));

        let buttonClose = this.modal_pane.popup.find("[action='CloseImage']");
        buttonClose.on("click", this.remove.bind(this));

        $("html").css("overflow", "hidden");
        $("body").css("overflow", "hidden");


        this.showImage();

    }

    /**
     *  Return the html to be shown inside a ModalPane
     * @returns {string}
     */
    createPopupContents() {
        let html = "";
        html += "<div class='ImagePopup'>";

        html += "<div class='Header'><div class='Contents'><a class='Button' action='ZoomImage'></a><a class='Button' action='CloseImage' default_action></a></div></div>";

        html += "<div class='Base'>";

        html += "<div class='Contents'>";

        html += "<a class='Button' action='PrevImage'></a><a class='Button' action='NextImage'></a>";

        html += "</div>";//contents

        html += "</div>";//base

        html += "<div class='Footer'><div class='Contents'></div></div>";

        html += "</div>";//ImagePopupPanel

        return this.processPopupContents(html);
    }

    /**
     * Called after createPopupContents() to allow parsing or changing the default content
     * @param html {string}
     * @returns {string}
     */
    processPopupContents(html) {
        return html;
    }

    remove() {

        $("html").css("overflow", "");
        $("body").css("overflow", "");

        this.disableZoom();
        this.modal_pane.close();

    }

    fetchImage() {


        let aelm = $(this.collection.slice(this.pos, this.pos + 1));

        if (!aelm) {
            console.log("Requested position: " + this.pos + " out of bounds for this image collection.");
            return;
        }

        let itemClass = aelm.attr("itemClass");
        let itemID = aelm.attr("itemID");

        let url = new URL(STORAGE_LOCAL, location.href);
        url.searchParams.set("cmd", "image");
        url.searchParams.set("class", itemClass);
        url.searchParams.set("id", itemID);

        let caption = aelm.attr("caption");

        let contents = this.modal_pane.popup.find(".Footer .Contents");
        if (caption) {
            contents.html("<div class='Caption'>" + caption + "</div>");
        } else {
            contents.html("");
        }

        let viewport = this.modal_pane.popup;
        let loader = this.modal_pane.popup.find(".Base .Contents");

        $(viewport).data("imageURL", url.href);

        $(viewport).css("background-image", "url(" + url.href + ")");
        $(viewport).css("background-size", "contain");

        $(loader).removeClass("cover-spin");


        // $('<img/>').attr('src', href).load(function() {
        //
        //     //.find(".ImagePopup");
        //     $(img).css("background-image", "url("+href+")");
        //
        //     $(loader).removeClass("cover-spin");
        //
        //     $(this).remove();
        // })
    }

    disableZoom() {
        console.log("Disable zoom");
        let viewport = this.modal_pane.popup;

        $(viewport).css("background-position", "");
        $(viewport).css("background-size", "contain");
        $(viewport).attr("zoom-enabled", 0);

        this.modal_pane.popup.find(".Button[action='PrevImage']").css("display","");
        this.modal_pane.popup.find(".Button[action='NextImage']").css("display","");

        $(viewport).off("touchstart mousedown");
        $(viewport).off("touchend mouseup");
        $(viewport).off("touchmove mousemove");
    }

    zoomImage(event) {

        event.stopPropagation();
        event.preventDefault();

        let viewport = this.modal_pane.popup;

        let zoom_enabled = $(viewport).attr("zoom-enabled");

        if (zoom_enabled==1) {

            this.disableZoom();

        }
        else {
            console.log("Enabling zoom");
            $(viewport).css("background-size", "");
            $(viewport).attr("zoom-enabled", 1);

            this.modal_pane.popup.find(".Button[action='PrevImage']").css("display","none");
            this.modal_pane.popup.find(".Button[action='NextImage']").css("display","none");

            //center image inside the background area
            let image = new Image();
            image.src = $(viewport).data("imageURL");
            //console.log("Image size ["+image.width + "," + image.height+"]");
            //console.log("Viewport size ["+$(viewport).width() + "," + $(viewport).height()+"]");

            $(viewport).data("imageWidth", image.width);
            $(viewport).data("imageHeight", image.height);


            let bX = ($(viewport).width() / 2.0) - (image.width / 2.0);
            let bY = ($(viewport).height() / 2.0) - (image.height / 2.0);
            $(viewport).data("bX", bX);
            $(viewport).data("bY", bY);

            $(viewport).css("background-position", bX+"px "+ bY + "px");

            $(viewport).on("touchstart mousedown", function(event){
                //event.stopPropagation();
                //event.preventDefault();

                $(viewport).data("down", 1);
                //console.log("Touch start");
            }.bind(viewport));

            $(viewport).on("touchend mouseup", function(event){
                //event.stopPropagation();
                //event.preventDefault();

                $(viewport).data("down", 0);
                //console.log("Touch end");
            }.bind(viewport));

            $(viewport).on("touchmove mousemove", function(event){

                event.stopPropagation();
                event.preventDefault();

                if ($(viewport).data("down") != 1) return false;

                var mX = event.clientX;
                if (event.clientX == null) {
                    mX = event.touches[0].clientX;
                }
                if (mX<0)mX=0;

                if (mX>$(viewport).width()) {
                    mX = $(viewport).width();
                }

                var mY = event.clientY;
                if (event.clientY == null) {
                    mY = event.touches[0].clientY;
                }
                if (mY<0)mY=0;
                if (mY>$(viewport).height()) {
                    mY = $(viewport).height();
                }

                console.log("mX="+mX+" mY="+mY);

                let bX = $(viewport).data("bX");
                let bY = $(viewport).data("bY");

                if ($(viewport).data("imageWidth")> $(viewport).width()) {
                    let dX = ($(viewport).data("imageWidth") - $(viewport).width()) / $(viewport).width();
                    bX = dX * mX * -1;
                }

                if ($(viewport).data("imageHeight")> $(viewport).height()) {
                    let dY = ($(viewport).data("imageHeight") - $(viewport).height()) / $(viewport).height();
                    bY = dY * mY * -1;
                }

                $(viewport).css("background-position", bX+"px "+ bY + "px");
                return true;

            }.bind(viewport));


        }


    }
    showImage() {

        let loader = this.modal_pane.popup.find(".Base .Contents");
        $(loader).addClass("cover-spin");

        setTimeout(function (event) {
            this.fetchImage();
        }.bind(this), 100);

    }
}

onPageLoad(function () {

    let gallery_view = new ImagePopup();

    $("A.ImagePopup:not([href])").on("click", function () {
        gallery_view.popupImage($(this));
        return false;
    });


});
