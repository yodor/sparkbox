class GalleryTape extends Component {

    constructor() {
        super();
        this.setClass(".GalleryTape");
        this.duration = 200;
    }

    initialize() {
        super.initialize();
        $(this.selector() + " .button.left").on("click", this.prevImage.bind(this));
        $(this.selector() + " .button.right").on("click", this.nextImage.bind(this));

        $(document).on("ImagePopup", function (e) {

            //console.log("ImagePopup::event - Relation: " + e.relation + " Message: " + e.message);

            if (e.relation != this.name) return;

            switch (e.message) {
                case "onPrevImage":
                    this.prevImage();
                    break;
                case "onNextImage":
                    this.nextImage();
                    break;

            }


        }.bind(this));
        $(this.selector()).data("handler", this);

        let elm = $(this.selector());
        const listener = new SwipeListener(elm);
        elm.on("SwipeAction", function(e) {

            if (e.message == "right") {
                this.nextImage();
            }
            else if (e.message == "left") {
                this.prevImage();
            }

        }.bind(this));
    }

    setDuration(duration) {
        this.duration = duration;
    }

    prevImage() {
        if ($(this.selector()).data("execute")) return;

        $(this.selector()).data("execute", true);

        let slots = $(this.selector() + " .slots");
        let last = $(this.selector() + " .slots .slot:last");

        let w = last.outerWidth(true);

        last.prependTo(slots);

        slots.css("left", -w);


        let first = $(this.selector() + " .slots .slot:first");

        slots.animate({
            left: 0
        }, {
            duration: this.duration,

            step: function (now, fx) {
                slots.css("left", now);
            }.bind(this),

            easing: "swing",

            complete: function () {

                $(this.selector()).data("execute", false);

            }.bind(this)
        });

    }

    nextImage() {

        if ($(this.selector()).data("execute")) return;
        $(this.selector()).data("execute", true);

        let slots = $(this.selector() + " .slots");
        let first = $(this.selector() + " .slots .slot:first");

        let w = first.outerWidth(true);

        slots.animate({
            left: -w
        }, {
            duration: this.duration,

            step: function (now, fx) {
                slots.css("left", now);
            }.bind(this),

            easing: "swing",

            complete: function () {

                first.appendTo(slots);

                slots.css("left", "");
                $(this.selector()).data("execute", false);

            }.bind(this)
        });
    }


}
