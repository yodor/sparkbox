function isMob() {
    if (navigator.userAgent.match(/Android/i)
        || navigator.userAgent.match(/webOS/i)
        || navigator.userAgent.match(/iPhone/i)
        || navigator.userAgent.match(/iPad/i)
        || navigator.userAgent.match(/iPod/i)
        || navigator.userAgent.match(/BlackBerry/i)
        || navigator.userAgent.match(/Windows Phone/i)
    ) {
        return true;
    } else {
        return false;
    }
}

class Point {
    constructor(x, y) {
        this.x = x;
        this.y = y;
    }
}

class MenuBar extends Component {

    constructor() {
        super();
        this.class = ".MenuBar:not([noattach])";

        this.menu = "";
        /**
         *
         * @type {HTMLElement} .MenuBar .ItemList
         */
        this.itemList = null;
        /**
         *
         * @type {HTMLElement} .MenuBar .toggle
         */
        this.toggle = null;

    }
    isTouchEnabled() {
        return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
    }
    // selector() {
    //     let result = super.selector();
    //     if (this.menu) {
    //         result+="[menu='" + this.menu + "']";
    //     }
    //     return result;
    // }
    //
    // setMenu(menu) {
    //     this.menu = menu;
    // }

    initialize() {

        super.initialize();

        if (!this.element) {
            //console.log("Not initializing for having noattach attribute");
            return;
        }


        this.toggle = this.element.querySelector(".toggle");
        this.toggle.addEventListener("click", ()=>this.toggleMenu());

        this.itemList = this.element.querySelector(".ItemList");
        this.itemList.querySelectorAll(".Item").forEach((barItem)=>{

            if (!this.isTouchEnabled()) {
                barItem.addEventListener("mouseenter", () => this.menuEnter(barItem));
                barItem.addEventListener("mouseleave", () => this.menuLeave(barItem));
            }

            barItem.addEventListener("click", (event)=>this.menuAction(barItem, event));

        });

    }

    toggleMenu() {

        if (this.element.hasAttribute("open")) {
            this.element.removeAttribute("open");
        }
        else {
            this.element.setAttribute("open", "");
        }

    }

    /**
     *
     * @param item {HTMLElement}
     * @param event {Event}
     */
    menuAction(item, event)
    {
        event.preventDefault();
        event.stopPropagation();

        const href = item.querySelector("[href]")?.getAttribute("href");
        //console.log(link);
        if (href) {
            window.location.href = href;
        }
    }



    leaveAll() {

        this.itemList.querySelectorAll(".Item").forEach((barItem)=>{
            this.menuLeave(barItem);
        });

    }

    /**
     * Add/Remove css class Hover to 'item'
     * @param item {HTMLElement} .Item
     * @param mode {boolean}
     */
    setItemSelected(item, mode) {

        if (!item) return;

        if (mode) {
            item.classList.add("Hover");
        }
        else {
            item.classList.remove("Hover");
        }
    }

    /**
     * Set selected and open submenu if any
     * @param item {HTMLElement} .Item
     */
    menuEnter(item) {

        const subMenu = item.querySelector(".ItemList.Submenu");

        if (subMenu?.childNodes.length>0) {

            const pos = new Point(0,0);
            const rect = item.getBoundingClientRect()

            if (item.closest(".ItemList.Submenu")) {
                //open submenu to the right
                pos.x = rect.width;
                pos.y = 0;
            }
            else {
                //open submenu down below
                pos.x = 0;
                pos.y = rect.height;
            }

            subMenu.setAttribute("open", "");

            subMenu.style.left = pos.x + "px";
            subMenu.style.top = pos.y + "px";

        }

        this.setItemSelected(item, true);

    }


    /**
     *
     * @param mitem {HTMLElement}
     */
    menuLeave(mitem) {

        mitem.querySelectorAll(".ItemList.Submenu").forEach((item)=> {
            item.removeAttribute("open");
        });

        this.setItemSelected(mitem, false);
    }




}
