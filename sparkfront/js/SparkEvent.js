class SparkEvent extends Event {

    static DOM_UPDATED = "DOMUpdate";
    /**
     *
     * @param message {string}
     * @param source {object}
     */
    constructor(message, source= null) {
        if (source) {
            super(source.constructor.name);
        }
        else {
            super(message);
            //console.log("Creating Event: "+message);
        }

        this.source = source;
        this.message = message;
        this.time = new Date();
    }

    isEvent(message) {
        return (this.message == message);
    }

}