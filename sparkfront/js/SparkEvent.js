class SparkEvent extends Event {
    /**
     *
     * @param message {string}
     * @param source {object}
     */
    constructor(message, source) {
        super(source.constructor.name);
        this.source = source;
        this.message = message;
        this.time = new Date();

    }

    isEvent(message) {
        return (this.message == message);
    }

}