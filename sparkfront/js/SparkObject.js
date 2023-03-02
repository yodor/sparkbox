class SparkObject {

    constructor() {
        this.observers = [];
    }

    /**
     * add callback function
     * @param func {Function}
     */
    addObserver(func) {
        this.observers.push(func);
    }

    /**
     * remove callback function
     * @param func {Function}
     */
    removeOvserver(func) {
        this.observers = this.observers.filter(
            function (item) {
                if (item !== func) {
                    return item;
                }
            }
        );
    }

    /**
     * Notify subscribed observer callbacks
     * @param sparkEvent {SparkEvent}
     */
    notify(sparkEvent) {
        //var scope = thisObj || window;
        this.observers.forEach(function (item) {
            item(sparkEvent);
        });
    }
}