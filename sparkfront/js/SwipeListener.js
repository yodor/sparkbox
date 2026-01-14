class SwipeListener extends SparkObject
{
    static SWIPE_LEFT = "left";
    static SWIPE_RIGHT = "right";

    static SWIPE_START = "start";
    static SWIPE_MOVE = "move";
    static SWIPE_END = "end";

    /**
     *
     * @param viewport {HTMLElement}
     */
    constructor(viewport) {
        super();
        // Touch support for mobile swipe gestures
        this.isDragging = false;
        this.startX = 0;
        this.diff = 0;
        this.viewport = viewport;
        this.threshold = this.viewport.clientWidth * 0.2;

        this.viewport.addEventListener('touchstart', (event)=>this.touchStartHandler(event), { passive: true });
        this.viewport.addEventListener('touchmove', (event)=>this.touchMoveHandler(event), { passive: false });
        this.viewport.addEventListener('touchend', (event)=>this.touchEndHandler(event));

        this.viewport.addEventListener('mousedown', (event)=>this.touchStartHandler(event), { passive: true });
        this.viewport.addEventListener('mousemove', (event)=>this.touchMoveHandler(event), { passive: false });
        this.viewport.addEventListener('mouseup', (event)=>this.touchEndHandler(event));

    }

    touchStartHandler(e)
    {
        this.diff = 0;
        this.startX = e.touches ? e.touches[0].clientX : e.x;
        this.isDragging = true;
        this.notify(new SparkEvent(SwipeListener.SWIPE_START, this));
    }

    touchMoveHandler(e)
    {
        if (!this.isDragging) return;
        const currentX = e.touches ? e.touches[0].clientX : e.x;
        this.diff = currentX - this.startX;
        e.preventDefault();
        this.notify(new SparkEvent(SwipeListener.SWIPE_MOVE, this));
    }

    touchEndHandler(e)
    {
        if (!this.isDragging) return;
        this.isDragging = false;

        const endX = e.changedTouches ? e.changedTouches[0].clientX : e.x;
        this.diff = endX - this.startX;

        if (Math.abs(this.diff) > this.threshold) {
            if (this.diff < 0) {
                this.notify(new SparkEvent(SwipeListener.SWIPE_LEFT, this));
            } else {
                this.notify(new SparkEvent(SwipeListener.SWIPE_RIGHT, this));
            }
        }

        this.notify(new SparkEvent(SwipeListener.SWIPE_END, this));
    }


}