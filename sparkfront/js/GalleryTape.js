class GalleryTape extends Component {

    constructor() {
        super();
        this.class = ".GalleryTape";
        this.duration = 200;
    }

    initialize() {

        super.initialize();

        const instance = this;

        this.element.querySelector(".button.left")?.addEventListener("click", (event)=>{
            instance.prevImage(event);
        });

        this.element.querySelector(".button.right")?.addEventListener("click", (event)=>{
            instance.nextImage(event);
        });

        this.slots = this.element.querySelector(".slots");

        document.addEventListener("ImagePopup", (event)=>{

            if (event.relation != this.name) return;

            switch (event.message) {
                case "onPrevImage":
                    instance.prevImage();
                    break;
                case "onNextImage":
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
