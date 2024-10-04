class ToolTip {
    constructor() {
        const template = document.createElement('template');
        template.innerHTML = "<div class='TooltipPanel'></div>";
        this.tipElement = template.content.firstChild;
    }

    /**
     *
     * @param element {HTMLElement}
     * @param event
     */
    show(element, event) {

        this.hide(element,event);

        this.tipElement.textContent = element.getAttribute("tooltip");

        let left = event.pageX + 25;
        let top = event.pageY + 25;

        this.tipElement.style.left = left + "px"
        this.tipElement.style.top = top + "px"
        this.tipElement.style.display = "block";

        document.body.append(this.tipElement);
        // setTimeout(function(event){
        //         hideTooltip(element, event);
        // }, 3000);
    }

    /**
     *
     * @param element {HTMLElement}
     * @param event
     */
    hide(element, event) {
        document.querySelector(".TooltipPanel")?.remove();
    }

    /**
     *
     * @param element {HTMLElement}
     */
    assignListeners(element) {
        if (!element) element = "body";

        const toolTip = this;

        document.querySelectorAll(element+" [tooltip]").forEach((element) => {
            element.addEventListener("mouseenter", function(event){
                toolTip.show(this,event);
            });
            element.addEventListener("mouseleave", function(event){
                toolTip.hide(this,event);
            });
        });
    }

}

const toolTip = new ToolTip();

onPageLoad(function () {
    toolTip.assignListeners();
}, true);