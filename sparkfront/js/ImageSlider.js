/**
 * The script of the ImageSlider component (lib/components/ImageSlider.php): the arrows, the dots, a swipe (touch or
 * mouse drag) and the autoplay move the photos; after the last photo it comes back to the first.
 *
 * effect 'slide': the row of photos moves to the left edge of the current photo - one photo in view, several
 * (--slider-per-view) or a strip of photos in their own widths all move the same way; the last position is the
 * one that shows the last photo at the right edge.
 * effect 'fade': the photos are stacked, the current one has the class 'active' (the css fades it in).
 *
 * The ImagePopup of a photo shows the photos of the slider (relation = the slider name); when it closes the slider
 * shows the photo the popup was on.
 */
class ImageSlider extends Component {

    /**
     * Start the slider of the component named 'name'
     * @param name {string}
     * @param options {{effect: string, autoplay: boolean, delay: number}}
     * @returns {ImageSlider}
     */
    static Start(name, options) {
        const slider = new ImageSlider();
        slider.setName(name);
        slider.effect = options.effect ?? "slide";
        slider.autoplay = options.autoplay ?? false;
        slider.delay = options.delay ?? 4000;
        slider.initialize();
        return slider;
    }

    constructor() {
        super();
        this.class = ".ImageSlider";
        this.effect = "slide";
        this.autoplay = false;
        this.delay = 4000;

        this.index = 0;
        this.timer = null;
        //a drag moved the photos - the click that ends it does not open the photo
        this.dragged = false;

        this.viewport = null;
        this.items = [];
        this.dots = [];
        this.swipe = null;
    }

    initialize() {
        super.initialize();

        this.viewport = this.element.querySelector(".viewport");
        this.items = [...this.viewport.children];
        this.dots = [...this.element.querySelectorAll(".dots .dot")];

        if (this.items.length < 2) return;

        this.element.querySelector(".nav-arrow.prev")?.addEventListener("click", () => this.prev());
        this.element.querySelector(".nav-arrow.next")?.addEventListener("click", () => this.next());
        this.dots.forEach((dot, index) => dot.addEventListener("click", () => this.goTo(index)));

        //capture: runs before the popup and the link of the photo
        this.viewport.addEventListener("click", (event) => {
            if (!this.dragged) return;
            event.preventDefault();
            event.stopPropagation();
        }, true);

        if (this.effect !== "fade") {
            this.swipe = new SwipeListener(this.viewport);
            this.swipe.threshold = Math.min(this.element.clientWidth * 0.2, 100);
            this.swipe.addObserver((event) => this.onSwipe(event));
        }

        if (document.imagePopup instanceof ImagePopup) {
            document.imagePopup.addObserver((event) => this.onPopup(event));
        }

        //no autoplay while the pointer is over the slider
        this.element.addEventListener("mouseenter", () => this.stop());
        this.element.addEventListener("mouseleave", (event) => {
            if (this.swipe) this.swipe.touchEndHandler(event);
            this.start();
        });

        window.addEventListener("resize", () => this.update());
        //a strip measures its photos - again as each one loads (the slider starts when the page is parsed)
        if (this.element.classList.contains("strip")) {
            this.viewport.querySelectorAll("img").forEach((image) => {
                if (!image.complete) image.addEventListener("load", () => this.update(), {once: true});
            });
        }

        this.element.classList.add("ready");
        this.update();
        this.start();
    }

    /**
     * The last position - the photos after it are in view already
     * @returns {number}
     */
    lastIndex() {
        if (this.effect === "fade") return this.items.length - 1;
        const room = this.viewport.clientWidth;
        const end = this.viewport.scrollWidth;
        for (let index = 0; index < this.items.length; index++) {
            if (end - this.items[index].offsetLeft <= room + 1) return index;
        }
        return this.items.length - 1;
    }

    /**
     * The left edge of the photo at 'index' in the row
     * @param index {number}
     * @returns {number}
     */
    offset(index) {
        return this.items[index] ? this.items[index].offsetLeft : 0;
    }

    update() {
        this.index = Math.min(this.index, this.lastIndex());
        if (this.effect === "fade") {
            this.items.forEach((item, index) => item.classList.toggle("active", index === this.index));
        }
        else {
            this.viewport.style.transform = "translateX(" + (-this.offset(this.index)) + "px)";
        }
        this.dots.forEach((dot, index) => dot.classList.toggle("active", index === this.index));
    }

    goTo(index) {
        this.index = Math.max(0, Math.min(index, this.lastIndex()));
        this.update();
    }

    next() {
        this.index = (this.index >= this.lastIndex()) ? 0 : this.index + 1;
        this.update();
    }

    prev() {
        this.index = (this.index <= 0) ? this.lastIndex() : this.index - 1;
        this.update();
    }

    start() {
        if (!this.autoplay) return;
        this.stop();
        this.timer = setInterval(() => this.next(), this.delay);
    }

    stop() {
        if (this.timer) clearInterval(this.timer);
        this.timer = null;
    }

    /**
     * @param event {SparkEvent}
     */
    onSwipe(event) {
        if (event.isEvent(SwipeListener.SWIPE_START)) {
            this.dragged = false;
            this.viewport.classList.add("sliding");
            this.stop();
        }
        else if (event.isEvent(SwipeListener.SWIPE_MOVE)) {
            const diff = event.source.diff;
            if (Math.abs(diff) > 5) this.dragged = true;
            this.viewport.style.transform = "translateX(" + (diff - this.offset(this.index)) + "px)";
        }
        else if (event.isEvent(SwipeListener.SWIPE_LEFT)) {
            this.next();
        }
        else if (event.isEvent(SwipeListener.SWIPE_RIGHT)) {
            this.prev();
        }
        else if (event.isEvent(SwipeListener.SWIPE_END)) {
            this.viewport.classList.remove("sliding");
            this.update();
            this.start();
        }
    }

    /**
     * @param event {SparkEvent}
     */
    onPopup(event) {
        if (event.isEvent(ImagePopup.EVENT_OPENED)) {
            this.stop();
        }
        else if (event.isEvent(ImagePopup.EVENT_CLOSED)) {
            if (event.source.relation === this.name) this.goTo(event.source.pos);
            this.start();
        }
    }
}
