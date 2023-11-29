class ModalPopup {
    static indexCounter = -1;

    constructor() {

        this.className = ".ModalPane";

        this.modal_content = "<div class='ModalPane'></div>";
        this.fullscreen = false;

        this.index = -1;

        /**
         *
         * @type {jQuery}
         */
        this.popup = null;

        /**
         *
         * @type {jQuery}
         */
        this.pane = null;

        $(window).on("resize", function (event) {
            this.centerContents();
        }.bind(this));
    }

    /**
     * JQuery selector
     * @param html_element
     */
    showContent(html_element) {

        ModalPopup.indexCounter++;

        this.index = ModalPopup.indexCounter;

        html_element.css("visibility", "hidden");

        let pane = $(this.modal_content);
        pane.attr("index", this.index);

        pane.append(html_element);


        $("body").append(pane);

        this.pane = $(this.visualSelector());
        this.popup = this.pane.children().first();

        this.show();

    }

    visualSelector() {
        return this.className + "[index='" + this.index + "']";
    }

    show() {

        this.pane.data("control_object", this);

        this.pane.on("click", function (event) {
            this.paneClicked();
        }.bind(this));


        if (!this.fullscreen) {
            this.popup.css("display", "block");
        }

        this.makeResizable();

        this.makeMovable();

        this.popup.css("visibility", "visible");

        $.event.trigger({
            type: "ModalPane",
            message: "contentShown",
            time: new Date(),
            pane: this,
        });

    }

    close() {
        if (this.pane) {
            this.pane.remove();
        }
    }

    paneClicked(event) {

    }

    centerContents() {

        $(this.popup).css("width", "auto");
        $(this.popup).css("height", "auto");
        // this.popup.css({});

        if (!this.popup)return;

        let windowWidth = $(window).width(); //retrieve current window width
        let windowHeight = $(window).height(); //retrieve current window height

        let width = $(this.popup).width();
        let height = $(this.popup).height();

        if (width==0 || height==0) return;


        //console.log("Width: "+width+ " Height: " + height);

        let left = ((windowWidth - width) / 2.0);
        let top = ((windowHeight - height) / 2.0);

        this.popup.css("left", left);
        this.popup.css("top", top);
        this.popup.css("width", width);
        //this.popup.css("height", height);



    }

    makeMovable() {
        let caption = this.popup.find(".Caption").first();
        if (caption.get(0)) {
            caption.mousedown(function (event) {
                if (event.which != 1) return;

                let pos = this.popup.offset();
                let deltaX = event.pageX - pos.left;
                let deltaY = event.pageY - pos.top;

                $(window).mousemove(function (event1) {

                    if (event1.which != 1) return;

                    this.popup.css("left", event1.pageX - deltaX);
                    this.popup.css("top", event1.pageY - deltaY);

                }.bind(this));

            }.bind(this));

            caption.mouseup(function (event) {
                $(window).unbind("mousemove");
            });
        }
    }

    /*Make resizable div by Hung Nguyen*/
    makeResizable() {

        const element = this.popup.get(0); //document.querySelector(div);

        const resizers = this.popup.find(".resizer");//document.querySelectorAll(div + ' .resizer');

        const minimum_size = 20;
        let original_width = 0;
        let original_height = 0;
        let original_x = 0;
        let original_y = 0;
        let original_mouse_x = 0;
        let original_mouse_y = 0;

        for (let i = 0; i < resizers.length; i++) {
            const currentResizer = resizers[i];

            currentResizer.addEventListener('mousedown', function (e) {
                if (e.button === 0) {
                    e.preventDefault();

                    original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''));
                    original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''));
                    original_x = element.getBoundingClientRect().left;
                    original_y = element.getBoundingClientRect().top;
                    original_mouse_x = e.pageX;
                    original_mouse_y = e.pageY;
                    window.addEventListener('mousemove', resize)
                    window.addEventListener('mouseup', stopResize)
                }
            })

            function resize(e) {
                if (currentResizer.classList.contains('bottom-right')) {
                    const width = original_width + (e.pageX - original_mouse_x);
                    const height = original_height + (e.pageY - original_mouse_y)
                    if (width > minimum_size) {
                        element.style.width = width + 'px'
                    }
                    if (height > minimum_size) {
                        element.style.height = height + 'px'
                    }
                } else if (currentResizer.classList.contains('bottom-left')) {
                    const height = original_height + (e.pageY - original_mouse_y)
                    const width = original_width - (e.pageX - original_mouse_x)
                    if (height > minimum_size) {
                        element.style.height = height + 'px'
                    }
                    if (width > minimum_size) {
                        element.style.width = width + 'px'
                        element.style.left = original_x + (e.pageX - original_mouse_x) + 'px'
                    }
                } else if (currentResizer.classList.contains('top-right')) {
                    const width = original_width + (e.pageX - original_mouse_x)
                    const height = original_height - (e.pageY - original_mouse_y)
                    if (width > minimum_size) {
                        element.style.width = width + 'px'
                    }
                    if (height > minimum_size) {
                        element.style.height = height + 'px'
                        element.style.top = original_y + (e.pageY - original_mouse_y) + 'px'
                    }
                } else {
                    const width = original_width - (e.pageX - original_mouse_x)
                    const height = original_height - (e.pageY - original_mouse_y)
                    if (width > minimum_size) {
                        element.style.width = width + 'px'
                        element.style.left = original_x + (e.pageX - original_mouse_x) + 'px'
                    }
                    if (height > minimum_size) {
                        element.style.height = height + 'px'
                        element.style.top = original_y + (e.pageY - original_mouse_y) + 'px'
                    }
                }
            }

            function stopResize() {
                window.removeEventListener('mousemove', resize)
            }
        }
    }


}


$(document).on("keyup", function (event) {

    let pane = $(".ModalPane[index]").last();
    if (!pane.get(0)) return true;

    let cancel_button = pane.find("[action='cancel']").first();
    let confirm_button = pane.find("[action='confirm']").first();
    let default_button = pane.find("[action][default_action]").first();

    if (event.which == 27) {

        if (cancel_button.get(0)) {
            cancel_button.click();
        } else if (confirm_button.get(0)) {
            confirm_button.click();
        } else {
            pane.click();
        }
        return false;
    } else if (event.which == 13) {

        if (confirm_button.get(0)) {
            confirm_button.click();
        } else if (default_button.get(0)) {
            default_button.click();
        } else {
            return true;
        }

        return false;
    }

    return false;
});
