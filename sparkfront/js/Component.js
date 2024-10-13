class Component extends SparkObject
{
    /**
     * Create a DOM Element selector
     * The element attributes can be specified using the setter methods
     * 
     */
    constructor() {
        super();

        //selector items
        this.class = ".Component"
        this.name = "";
        this.field = "";
        this.id = "";

        /**
         *
         * @type {HTMLElement}
         */
        this.element = null;
    }

    /**
     *
     * @param name {string}
     */
    setName(name) {
        this.name = name;

    }

    getName() {
        return this.name;
    }

    /**
     *
     * @param field {string}
     */
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
     * Build and return selector string using the component 'class' property
     * Appends name, field and ID attributes if set
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

    /**
     * Query the document and return the dom Element of this component -
     * Using this.selector()
     * @returns {HTMLElement}
     */
    getElement() {
        return this.element;
    }

    initialize() {

        this.element = document.querySelector(this.selector());
        if (! (this.element instanceof Element)) {
            console.log("DOM query failed: " + this.selector());
        }
        else {
           // console.log("Element created for: " + this.selector());
        }

    }

}