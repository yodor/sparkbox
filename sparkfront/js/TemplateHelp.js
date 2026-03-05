class TemplateHelp {
    constructor() {
        this.request = new JSONRequest();
        this.request.setResponder("TemplateHelpResponder");
        this.request.setFunction("fetch");
        this.target = null;
    }

    /**
     *
     * @param elm {HTMLElement}
     */
    fetch(elm) {

        const path = elm.getAttribute('path');
        const sink = "." + elm.getAttribute('sink');

        this.target = document.querySelector(sink);

        this.request.setParameter("path", path);

        this.request.onSuccess = (result) => {
            this.target.innerHTML = result.response.message;

            elm.setAttribute("onClick", "javascript:document.templateHelp.toggle();");

            this.toggle();
        };

        this.request.start();

    }
    toggle()
    {
        const element = this.target;
        if (getComputedStyle(element).display === 'none') {
            // Restore previous or use preferred value
            element.style.display = 'block';
        } else {
            // Remember current display value and hide
            element.style.display = 'none';
        }
    }
}

document.templateHelp = new TemplateHelp();