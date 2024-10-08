class ToolTip {
    constructor() {
        this.tipElement = document.templateFactory.createElement("<div class='TooltipPanel'></div>");
        const instance = this;
        document.addEventListener("load", (event)=>{
            instance.assignListeners(document);
        });
        document.addEventListener(SparkEvent.DOM_UPDATED, (event)=>{
            instance.assignListeners(event.source);
        });
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


    assignListeners(parentNode) {

        parentNode.querySelectorAll("[tooltip]").forEach((element) => {
            if (element.tooltip instanceof ToolTip) return;

            element.tooltip = document.tooltip;

            element.addEventListener("mouseenter", function(event){

                element.tooltip.show(this,event);
            });
            element.addEventListener("mouseleave", function(event){
                element.tooltip.hide(this,event);
            });
        });
    }

}

document.tooltip = new ToolTip();
