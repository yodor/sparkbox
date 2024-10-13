
class SwipeListener extends SparkObject {
    /**
     *
     * @param elm {HTMLElement}
     */
    constructor(elm) {
        super();
        this.xDown = null;
        this.yDown = null;

        this.elm = elm;
        elm.addEventListener("touchstart", (event)=>this.handleTouchStart(event), {passive: true});
        elm.addEventListener("touchmove", (event)=>this.handleTouchMove(event), {passive: true});

    }

    emitEvent(action, evt) {
        //emit "SwipeListener" message=>action
        this.elm.dispatchEvent(new SparkEvent(action, this));
        this.onAction(action, evt);
    }

    onAction(action, evt) {

    }

    /**
     *
     * @param evt {TouchEvent}
     * @returns {TouchList|Touch[]|TouchList}
     */
    getTouches(evt) {
        // browser API || jQuery API
        return evt.touches || evt.originalEvent.touches;
    }

    /**
     *
     * @param evt {TouchEvent}
     */
    handleTouchStart(evt) {

        let firstTouch = this.getTouches(evt)[0];
        this.xDown = firstTouch.clientX;
        this.yDown = firstTouch.clientY;
    }

    /**
     *
     * @param evt {TouchEvent}
     */
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
                this.emitEvent("right", evt);
            } else {
                this.emitEvent("left", evt);
            }
        } else {
            if ( yDiff > 0 ) {
                this.emitEvent("down", evt);
            } else {
                this.emitEvent("up", evt);
            }
        }

        this.xDown = null;
        this.yDown = null;
    }
}