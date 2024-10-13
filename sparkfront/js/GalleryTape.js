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


        document.addEventListener("ImagePopup", (event)=>{

            if (event.source.relation != this.name) return;

            switch (event.message) {
                case ImagePopup.EVENT_POSITION_PREV:
                    this.prevImage();
                    break;
                case ImagePopup.EVENT_POSITION_NEXT:
                    this.nextImage();
                    break;

            }
        });



        try {

            const listener = new SwipeListener(this.element);
            this.element.addEventListener("SwipeAction", (event)=> {
                if (event.message == "right") {
                    this.nextImage();
                }
                else if (event.message == "left") {
                    this.prevImage();
                }
            });

        }
        catch(e) {
            console.log("Class SwipeListener is not defined: " + e);
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
