function ModalPopup() {
    this.modal_content = "<div class='ModalPane'></div>";
    this.fullscreen = false;
}

ModalPopup.prototype.popup = function () {
    return this.pane().children().first();
}

ModalPopup.prototype.pane = function () {
    return $(".ModalPane").last();
}

ModalPopup.prototype.showContent = function (content_html) {

    $("body").append(this.modal_content);

    let elm = $(content_html);
    elm.css("visibility", "hidden");

    this.pane().append(elm.get(0).outerHTML);

    this.show();

}

ModalPopup.prototype.showID = function (id) {
    if ($("#" + id).length < 1) {
        showAlert("Requested element #" + id + " was not found");
        return;
    }

    $("body").append(this.modal_content);

    let elm = $("#" + id);
    elm.data("originate_parent", elm.parent());

    this.pane().append(elm);
    elm.css("visibility", "hidden");
    this.show();

    elm.bind('destroyed', function () {

        let originate_parent = elm.data("originate_parent");
        originate_parent.append(elm);

        elm.attr("style", "");

    })

    //TODO:check if the elment need to go back to the dom after closing the modal pane
}

ModalPopup.prototype.show = function () {


    if (this.fullscreen) {
    } else {
        this.popup().css("display", "block");
    }

    this.popup().css("visibility", "visible");

    $.event.trigger({
        type: "ModalPopup",
        message: "onPopupVisible",
        time: new Date(),
        pane: this,
        popup: this.popup()
    });

    if (this.fullscreen) {

    } else {
        $(window).resize(function (event) {
            this.centerContents();
        }.bind(this));
    }

    this.pane().click(function (event) {
        this.paneClicked(event);
    }.bind(this));


    let caption = this.popup().find(".Caption");
    if (caption.get(0)) {
        caption.mousedown(function (event) {
            if (event.which != 1) return;

            let pos = this.popup().offset();
            let deltaX = event.pageX - pos.left;
            let deltaY = event.pageY - pos.top;

            $(window).mousemove(function (event1) {

                if (event1.which != 1) return;

                this.popup().css("left", event1.pageX - deltaX);
                this.popup().css("top", event1.pageY - deltaY);

            }.bind(this));

        }.bind(this));

        caption.mouseup(function (event) {
            $(window).unbind("mousemove");
        });
    }

    let last_pane = $("body").find(".ModalPane").last();
    if (last_pane.get(0)) {
        last_pane.data("control_object", this);
    }

    this.makeResizable();
}

ModalPopup.prototype.paneClicked = function (event) {

   console.log("ModalPopup: paneClicked");

}

ModalPopup.prototype.centerContents = function () {

    var windowWidth = $(window).width(); //retrieve current window width
    var windowHeight = $(window).height(); //retrieve current window height

    var width = this.popup().outerWidth(true);
    var height = this.popup().outerHeight(true);

    var left = ((windowWidth - width) / 2);
    var top = ((windowHeight - height) / 2);

    this.popup().css("left", left);
    this.popup().css("top", top);
    this.popup().css("min-width", width);
    this.popup().css("min-height", height);


}

/*Make resizable div by Hung Nguyen*/
ModalPopup.prototype.makeResizable = function () {


    let div = ".ModalPane .MessageDialog.resizable";

    const element = document.querySelector(div);

    const resizers = document.querySelectorAll(div + ' .resizer');

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
            e.preventDefault()
            original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''));
            original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''));
            original_x = element.getBoundingClientRect().left;
            original_y = element.getBoundingClientRect().top;
            original_mouse_x = e.pageX;
            original_mouse_y = e.pageY;
            window.addEventListener('mousemove', resize)
            window.addEventListener('mouseup', stopResize)
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


