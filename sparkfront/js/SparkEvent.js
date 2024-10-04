class SparkEvent extends Event {
    /**
     *
     * @param name {string}
     * @param source {object}
     */
    constructor(name, source) {
        super(name);
        this.name = name;
        this.source = source;
        this.time = new Date();
        this.message = "";

    }

    isEvent(name) {
        return (this.name == name);
    }

}