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

class MenuBarComponent extends Component {

    constructor() {
        super();
        this.class = ".MenuBarComponent:not([noattach])";
        this.menuBar = null;
        this.toggle = null;
    }

    toggleMenu() {

        const classNames = this.menuBar.classList;
        if (classNames.contains("normal")) {
            classNames.remove("normal");
            this.leaveAll();
        } else {
            classNames.add("normal");
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

    initialize() {

        super.initialize();


        if (!this.element) {
            //console.log("Not initializing for having noattach attribute");
            return;
        }

        this.menuBar = this.element.closest(".MenuBar");
        this.toggle = this.menuBar.querySelector(".toggle");

        this.toggle.addEventListener("click", ()=>this.toggleMenu());

        this.element.querySelectorAll(".MenuBarItemRenderer").forEach((barItem)=>{
            barItem.addEventListener("mouseenter", ()=>this.menuEnter(barItem));

            barItem.addEventListener("mouseleave", ()=>this.menuLeave(barItem));

            barItem.addEventListener("click", (event)=>this.menuAction(barItem, event));
        });

        this.element.querySelectorAll(".SubmenuItemRenderer").forEach((subItem)=>{
            subItem.addEventListener("mouseenter", ()=>this.submenuEnter(subItem));
            subItem.addEventListener("click", (event)=>this.menuAction(subItem, event));
        });


    }

    leaveAll() {

        this.element.querySelectorAll(".MenuBarItemRenderer").forEach((barItem)=>{
            barItem.menuLeave(barItem);
        });

    }

    /**
     * Add/Remove css class Hover to elm
     * @param elm {HTMLElement}
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
     * @param barItem {HTMLElement} MenuBarItemRenderer
     */
    menuEnter(barItem) {

        this.setItemSelected(barItem, true);

        if (barItem.getAttribute("have_submenu")) {

            const rect = barItem.getBoundingClientRect()
            this.submenuShow(barItem, 0, rect.height);

        }


    }

    /**
     *
     * @param mitem {HTMLElement}
     */
    menuLeave(mitem) {

        this.setItemSelected(mitem, false);

        mitem.querySelectorAll(".SubmenuRenderer").forEach((item)=> {
            item.classList.remove("normal");
            item.querySelectorAll(".SubmenuItemRenderer.Hover").forEach((item1)=>{
                this.submenuLeave(item1);
            });
        });

    }


    submenuEnter(smitem) {

        const pos = smitem.getBoundingClientRect()
        const parentPos = smitem.offsetParent.getBoundingClientRect();

        this.submenuLeave(smitem);
        this.submenuShow(smitem, pos.width, pos.bottom - parentPos.y - pos.height);
        this.setItemSelected(smitem, true);

    }

    /**
     * Set submenu visible(add class normal) and position at specified position [left, top]
     * @param mitem {HTMLElement} SubmenuItemRenderer | MenuBarItemRenderer
     * @param left
     * @param top
     */
    submenuShow(mitem, left, top) {

        //console.log(`Position: Left: ${left} Top: ${top}`);

        const subMenu = mitem.querySelector(".SubmenuRenderer");

        if (subMenu) {
            //make it visible
            subMenu.classList.add("normal");
            subMenu.style.left = left + "px";
            subMenu.style.top = top + "px";
        }

    }

    /**
     *
     * @param smitem {HTMLElement} .SubmenuItemRenderer
     */
    submenuLeave(smitem) {


        //leaveAll including current
        smitem.closest(".SubmenuRenderer").querySelectorAll(".SubmenuItemRenderer").forEach((item)=>{
                this.setItemSelected(item, false);

                item.querySelectorAll(".SubmenuRenderer").forEach((item1)=>{
                    item1.classList.remove("normal");
                })
        });

        this.setItemSelected(smitem, false);


    }




}
