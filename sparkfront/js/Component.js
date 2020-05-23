class Component extends SparkObject
{
    constructor() {
        super();
        this.name = "";
        this.field = "";
        this.class = "Component"

    }

    setName(name) {
        this.name = name;

    }

    getName() {
        return this.name;
    }

    setField(field) {
        this.field = field;

    }

    getField() {
        return this.field;
    }

    /**
     * Set the component class
     * @param name {string}
     */
    setClass(name) {
        this.class = name;
    }

    /**
     * Get the component class
     * @returns {string}
     */
    getClass() {
        return this.class;
    }

    /**
     *
     * @returns {string}
     */
    selector() {
        let result = this.class;
        if (this.name.length>0) {
            result+= "[name='"+this.name+"']";
        }
        if (this.field.length>0) {
            result+= "[field='"+this.field+"']";
        }
        return result;
    }

    /**
     * Attach with DOM element having attribute name='value' and set the selector property
     * @param value {string}
     */
    initialize() {

        console.log(this.constructor.name + "::initialize() " + this.selector());

    }
}