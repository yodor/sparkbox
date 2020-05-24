class Component extends SparkObject
{
    constructor() {
        super();
        this.name = "";
        this.field = "";
        this.class = ".Component"
        this.id = "";
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


    setID(id) {
        this.id = id;
    }

    getID() {
        return this.id;
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
        if (this.id.length>0) {
            result+= "#"+this.id;
        }
        return result;
    }

    component() {
        return $(this.selector());
    }

    /**
     * Attach with DOM element having attribute
     * @param value {string}
     */
    initialize() {

        if ($(this.selector()).get(0)) {
            console.log(this.constructor.name + "::initialize() " + this.selector());
        }
        else {
            throw "Component selector not found in DOM: " + this.selector();
        }

    }
}