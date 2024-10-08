class CallStack {

    constructor() {
        this.functions = Array();
        //const onLoad = this.execute.bind(this);
        window.addEventListener("load", (event)=>this.execute());
        document.addEventListener(SparkEvent.DOM_UPDATED, (event)=>this.execute());
    }

    execute() {

        // console.log("CallStack execute: " + this.functions.length);

        let persistent = Array();

        while (this.functions.length>0) {
            let callback = this.functions.shift();

            if (callback.stay) {
                persistent.push(callback);
            }
            try {
                if (typeof callback.function == 'function') {
                    //console.log("Calling function: " + crc32(callback.function.toString()));
                    callback.function();
                }
            }
            catch (e) {
                console.log("Error calling load function: " + e + " => " + e.stack);

            }
        }
        this.functions = persistent;
    }

    /**
     * Append OnLoadFunction object to the list of function to be called
     * @param func {OnLoadFunction}
     */
    append(func) {
        this.functions.push(func);
    }

}

class OnLoadFunction {
    constructor(func, stay) {
        this.function = func;
        this.stay = stay;
    }
}

document.sparkCallStack = new CallStack();

/**
 * Append function to the window 'onLoad' event call stack.
 * It is called usually just before finishRender() of the php component where initialization of javascript side code is needed after page is finished loading
 * @param func {Function} the function to call after window 'load' is fired
 * @param persistent {boolean} flag to specify this function should stay in the call stack after executing (only tooltip processTooltipContent uses this flag = true for now)
 *
 */
function onPageLoad(func, persistent=false)
{
    if (typeof func == 'function') {
        document.sparkCallStack.append(new OnLoadFunction(func,persistent));
    }
    else {
        console.log("onPageLoad: 'func' parameter is not a function");
    }
}