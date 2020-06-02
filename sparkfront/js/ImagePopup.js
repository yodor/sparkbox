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

        this.remove();
    }

    onClickPane(event) {

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

        let collection_selector = "A.ImagePopup[itemClass='" + itemClass + "']";

        let relation = aelm.attr("relation");
        if (relation) {
            collection_selector += "[relation='" + relation + "']";
            this.relation = relation;
        }

        this.collection = $(collection_selector);

        this.pos = this.collection.index(aelm);

        this.modal_pane.showContent($(this.createPopupContents()));


        let buttonNext = this.modal_pane.popup.find("[action='NextImage']");
        buttonNext.on("click", this.nextImage.bind(this));

        let buttonPrev = this.modal_pane.popup.find("[action='PrevImage']");
        buttonPrev.on("click", this.prevImage.bind(this));

        let buttonClose = this.modal_pane.popup.find("[action='CloseImage']");
        buttonClose.on("click", this.remove.bind(this));

        this.showImage();

    }

    /**
     *  Return the html to be shown inside a ModalPane
     * @returns {string}
     */
    createPopupContents() {
        let html = "";
        html += "<div class='ImagePopup'>";

        html += "<div class='Header'><div class='Contents'><a class='Button' action='CloseImage' default_action></a></div></div>";

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
        this.modal_pane.close();
    }

    fetchImage() {


        let aelm = this.collection.slice(this.pos, this.pos + 1);

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

    $("A.ImagePopup").on("click", function () {
        gallery_view.popupImage($(this));
        return false;
    });


});
