class SparkEvent {
    /**
     *
     * @param name {string}
     * @param source {object}
     */
    constructor(name, source) {
        this.name = name;
        this.source = source;
    }

    isEvent(name) {
        return (this.name == name);
    }

}