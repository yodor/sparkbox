/**
 * The content of a module path loaded into this page - without its frame (ContentFragmentResponder):
 *
 *   const fragment = new ContentFragment("/products/promos/");
 *   fragment.load(document.querySelector(".promos")).then(() => { ... });
 *
 * Loads the stylesheets and scripts the content needs that this page does not have yet, appends the content to
 * the element, adds the page components (dialog templates - once, inline scripts) and notifies
 * ContentFragment.EVENT_LOADED.
 */
class ContentFragment extends SparkObject {

    static EVENT_LOADED = "fragment_loaded";

    /**
     * @param url {string} the address of the module path
     */
    constructor(url) {
        super();
        this.url = url;
        this.request = new JSONRequest();
        this.request.setURL(url);
        this.request.setResponder("ContentFragmentResponder");
        this.request.setFunction("render");
        /**
         * The element of the content after load()
         * @type {HTMLElement|null}
         */
        this.element = null;
    }

    /**
     * Load the content and append it to 'parentElement'
     * @param parentElement {HTMLElement}
     * @returns {Promise<HTMLElement>} the element of the content
     */
    load(parentElement) {
        return new Promise((resolve, reject) => {
            this.request.onError = (error) => {
                reject(error);
            };
            this.request.onSuccess = (result) => {
                this.append(parentElement, result.response).then(resolve, reject);
            };
            this.request.start();
        });
    }

    /**
     * @param parentElement {HTMLElement}
     * @param response {object} contents, styles, scripts, page
     * @returns {Promise<HTMLElement>}
     */
    async append(parentElement, response) {

        await Promise.all((response.styles || []).map((href) => ContentFragment.LoadStyle(href)));
        //in their order - a script can need the one before it
        for (const src of (response.scripts || [])) {
            await ContentFragment.LoadScript(src);
        }

        const nodes = document.templateFactory.nodeList(response.contents);
        this.element = (nodes[0] instanceof HTMLElement) ? nodes[0] : null;
        document.templateFactory.appendNodes(parentElement, nodes);

        //the page components: a template that the page has already is not added again
        const pageNodes = document.templateFactory.nodeList(response.page || "").filter((node) => {
            return !(node.id && document.getElementById(node.id));
        });
        if (pageNodes.length > 0) {
            document.templateFactory.appendNodes(document.body, pageNodes);
        }

        const event = new SparkEvent(ContentFragment.EVENT_LOADED, this);
        this.notify(event);

        return this.element;
    }

    /**
     * True when the page has an element 'selector' whose 'attribute' is the address 'url'
     */
    static HasAddress(selector, attribute, url) {
        const href = new URL(url, window.location.href).href;
        //the files the page has in its bundles (HTMLHead::enableBundles)
        const bundled = (document.Spark && document.Spark.bundled) || [];
        if (bundled.some((address) => new URL(address, window.location.href).href === href)) return true;
        return Array.from(document.querySelectorAll(selector)).some((element) => {
            return new URL(element.getAttribute(attribute), window.location.href).href === href;
        });
    }

    static LoadStyle(href) {
        if (ContentFragment.HasAddress("link[rel='stylesheet']", "href", href)) return Promise.resolve();
        return new Promise((resolve) => {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = href;
            //a stylesheet that fails does not stop the content
            link.onload = link.onerror = () => resolve();
            document.head.appendChild(link);
        });
    }

    static LoadScript(src) {
        if (ContentFragment.HasAddress("script[src]", "src", src)) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = src;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Script not loaded: " + src));
            document.head.appendChild(script);
        });
    }
}
