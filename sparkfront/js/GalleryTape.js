class GalleryTape extends Component {

    constructor() {
        super();
        this.class = ".GalleryTape";
        this.duration = 200;
    }

    initialize() {

        super.initialize();

        this.element.querySelector(".button.left")?.addEventListener("click", (event)=> this.prevImage(event));

        this.element.querySelector(".button.right")?.addEventListener("click", (event)=> this.nextImage(event));

        this.slots = this.element.querySelector(".slots");

        const instance = this;
        document.addEventListener("ImagePopup", (event)=>{

            if (event.source.relation != this.name) return;

            switch (event.message) {
                case ImagePopup.EVENT_POSITION_PREV:
                    instance.prevImage();
                    break;
                case ImagePopup.EVENT_POSITION_NEXT:
                    instance.nextImage();
                    break;

            }
        });

        let elm = $(this.selector());

        const className = 'SwipeListener';
        if (Reflect.has(window, className)) {
            const listener = new SwipeListener(elm);
            elm.on("SwipeAction", function(e) {

                if (e.message == "right") {
                    this.nextImage();
                }
                else if (e.message == "left") {
                    this.prevImage();
                }

            }.bind(this));
        } else {
            //console.log(`Class ${className} is not defined`);
        }


    }

    setDuration(duration) {
        this.duration = duration;
    }

    prevImage() {

        let slot_last = this.slots.querySelector(".slot:last-of-type");
        let slot_first = this.slots.querySelector(".slot:first-of-type");

        this.slots.insertBefore(slot_last, slot_first);

    }

    nextImage() {

        let slot_last = this.slots.querySelector(".slot:last-of-type");
        let slot_first = this.slots.querySelector(".slot:first-of-type");

        this.slots.append(slot_first);
    }


}
