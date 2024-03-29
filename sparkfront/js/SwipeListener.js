class SwipeListener extends SparkObject {
    /**
     *
     * @param elm JQuery selector
     */
    constructor(elm) {
        super();
        this.xDown = null;
        this.yDown = null;
        this.SwipeEvent = {
            type: "SwipeAction",
            message: undefined,
            time: undefined,
            source: undefined,
        }
        this.elm = elm;
        elm.on('touchstart', this.handleTouchStart.bind(this));
        elm.on('touchmove', this.handleTouchMove.bind(this));
    }

    emitEvent(action,evt) {

        this.SwipeEvent.message = action;
        this.SwipeEvent.time = new Date();
        this.SwipeEvent.source = evt;

        this.elm.trigger(this.SwipeEvent);
        this.onAction(action, evt);
    }

    onAction(action, evt) {

    }

    getTouches(evt) {
        // browser API || jQuery API
        return evt.touches || evt.originalEvent.touches;
    }

    handleTouchStart(evt) {

        let firstTouch = this.getTouches(evt)[0];
        this.xDown = firstTouch.clientX;
        this.yDown = firstTouch.clientY;
    }

    handleTouchMove(evt) {
        if ( ! this.xDown || ! this.yDown ) {
            return;
        }

        let xUp = evt.touches[0].clientX;
        let yUp = evt.touches[0].clientY;

        let xDiff = this.xDown - xUp;
        let yDiff = this.yDown - yUp;

        if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {/*most significant*/
            if ( xDiff > 0 ) {
                this.emitEvent("right",evt);
            } else {
                this.emitEvent("left",evt);
            }
        } else {
            if ( yDiff > 0 ) {
                this.emitEvent("down",evt);
            } else {
                this.emitEvent("up",evt);
            }
        }

        this.xDown = null;
        this.yDown = null;
    }
}