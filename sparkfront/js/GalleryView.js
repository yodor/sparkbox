onPageLoad(function () {

    let gallery_view = new GalleryView();

    $("BODY").find("A.image_popup").live("click", function () {
        gallery_view.popupImage($(this));
        return false;
    });

    $(document).on("GalleryView", function (e) {

        switch (e.message) {
            case "onClickPane":
                gallery_view.onClickPane(e);
                break;
            case "onClickImage":
                gallery_view.onClickImage(e);
                break;
        }

    }.bind(this));

});

//create fullscreen image viewer
function GalleryView() {
    this.pos = -1;

    this.collection = new Array();

    this.related = "";
    //console.log("ImagePopup::popupContents Collection Lenght: " + this.collection.length +" Current Position: "+this.pos);

    this.modal_pane = new ModalPopup();
    this.modal_pane.fullscreen = true;

    this.margin_width = 0;
    this.margin_height = 0;

    $.event.trigger({
        type: "GalleryView",
        message: "onCreate",
        time: new Date(),
        object: this,
        instance: this
    });
}

GalleryView.prototype.onClickImage = function (event) {

//   console.log("Default Handler: onClickImage");
    this.closeImagePopup();

}
GalleryView.prototype.onClickPane = function (event) {

//   console.log("Default Handler: onClickPane");
    this.closeImagePopup();

}
GalleryView.prototype.nextImage = function (event) {
    event.stopPropagation();

    let old_position = this.pos;

    this.pos++;
    if (this.pos >= this.collection.length) this.pos = 0;
    this.startImageLoader();

    $.event.trigger({
        type: "GalleryView",
        message: "onNextImage",
        time: new Date(),
        rel: this.related,
        position: this.pos,
        old_position: old_position,
        event_source: event
    });

}

GalleryView.prototype.prevImage = function (event) {
    event.stopPropagation();

    let old_position = this.pos;

    this.pos--;
    if (this.pos < 0) this.pos = this.collection.length - 1;

    this.startImageLoader();

    $.event.trigger({
        type: "GalleryView",
        message: "onPrevImage",
        time: new Date(),
        rel: this.related,
        position: this.pos,
        old_position: old_position,
        event_source: event
    });

}

GalleryView.prototype.popupImage = function (aelm) {

    let related = aelm.attr("rel");


    if (related) {
        this.collection = $("A.image_popup[rel='" + related + "']");
        this.related = related;
    } else {
        this.collection = aelm;
    }

    this.pos = this.collection.index(aelm);

    this.modal_pane.showContent(this.createPopupContents());

    this.modal_pane.paneClicked = function (event) {

        $.event.trigger({
            type: "GalleryView",
            message: "onClickPane",
            time: new Date(),
            rel: this.related,
            position: this.pos,
            event_source: event,
        });

    }.bind(this);


    let buttonNext = this.modal_pane.popup().find("[action='NextImage']");
    buttonNext.unbind("click");
    buttonNext.bind("click", function (event) {
        this.nextImage(event);
    }.bind(this));

    let buttonPrev = this.modal_pane.popup().find("[action='PrevImage']");
    buttonPrev.unbind("click");
    buttonPrev.bind("click", function (event) {
        this.prevImage(event);
    }.bind(this));

    let buttonClose = this.modal_pane.popup().find("[action='CloseImagePopup']");
    buttonClose.unbind("click");
    buttonClose.bind("click", function (event) {
        this.closeImagePopup(event);
    }.bind(this));

    this.startImageLoader();

    $.event.trigger({
        type: "GalleryView",
        message: "onShowImagePopup",
        time: new Date(),
        rel: this.related,
        position: this.pos
    });


}

GalleryView.prototype.createPopupContents = function () {
    let html = "";
    html += "<div class='ImagePopup'>";

    html += "<div class='Header'><div class='Contents'><a class='Button' action='CloseImage'></a></div></div>";

    html += "<div class='Base'>";

    html += "<div class='Contents'>";

    //html += "<div class='ImagePart'>";

    html += "<a class='Button' action='PrevImage'></a><a class='Button' action='NextImage'></a>";

    html += "</div>";//contents

    html += "</div>";//base

    html += "<div class='Footer'><div class='Contents'><div class='Caption'></div></div></div>";

    html += "</div>";//ImagePopupPanel

    return this.processPopupContents(html);
}

GalleryView.prototype.processPopupContents = function (html) {
    return html;
}

GalleryView.prototype.closeImagePopup = function () {


    $.event.trigger({
        type: "GalleryView",
        message: "onCloseImagePopup",
        time: new Date(),
        rel: this.related,
        position: this.pos
    });

    this.modal_pane.pane().remove();
}

GalleryView.prototype.fetchImage = function () {


    let aelm = this.collection.slice(this.pos, this.pos + 1);

    if (!aelm) {
        console.log("Requested position: " + this.pos + " out of bounds for this image collection.");
        return;
    }

    let href = aelm.attr("href");
    let data_href = aelm.attr("data-href");
    if (data_href) href = data_href;

    let caption = aelm.attr("caption");


    if (caption) {
        let contents = this.modal_pane.popup().find(".Footer .Contents .Caption");
        contents.html(caption);
    }

    //console.log("ImagePopup::fetchImage["+this.pos+"] Fetching Image URL:"+href);


    // let screen_width = $(window).width();
    // let screen_height = $(window).height();
    //
    //
    // if (href.indexOf("?") != -1) href = href + "&max-width=" + (screen_width - this.margin_width) + "&max-height=" + (screen_height - this.margin_height);


    let viewport = this.modal_pane.popup();
    let loader = this.modal_pane.popup().find(".Base .Contents");

    $(viewport).css("background-image", "url("+href+")");
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

GalleryView.prototype.startImageLoader = function () {

    let loader = this.modal_pane.popup().find(".Base .Contents");
    $(loader).addClass("cover-spin");

    setTimeout(function(event) {
        this.fetchImage();
    }.bind(this), 100);

}

