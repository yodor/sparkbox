class ToolTip {
    constructor() {
        this.tipElement = document.templateFactory.nodeList("<div class='TooltipPanel'></div>")[0];

        window.addEventListener("load", (event) => this.assignListeners(document));
        document.addEventListener(SparkEvent.DOM_UPDATED, (event) => this.assignListeners(event.source));
        this.timerID = null;
    }

    /**
     *
     * @param element {HTMLElement}
     * @param event
     */
    show(element, event) {

        this.hide(element, event);

        this.tipElement.textContent = element.getAttribute("tooltip");

        let left = event.pageX + 25;
        let top = event.pageY + 25;

        this.tipElement.style.left = left + "px"
        this.tipElement.style.top = top + "px"
        this.tipElement.style.display = "block";

        document.body.append(this.tipElement);
        this.timerID = setTimeout((event) => {
             this.hide(element, event);
        }, 3000);
    }

    /**
     *
     * @param element {HTMLElement}
     * @param event
     */
    hide(element, event) {
        clearTimeout(this.timerID);

        document.querySelector(".TooltipPanel")?.remove();
    }


    assignListeners(parentNode) {

        parentNode.querySelectorAll("[tooltip]").forEach( (element) => {

            element.addEventListener("mouseenter", (event)=>document.tooltip.show(element,event));
            element.addEventListener("mouseleave", (event)=>document.tooltip.hide(element,event));

        });
    }

}

document.tooltip = new ToolTip();