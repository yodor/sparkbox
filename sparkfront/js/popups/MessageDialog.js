function MessageDialog(id) {
    this.text = "";
    this.caption = "Message";

    this.modal_pane = new ModalPopup();
    this.id = "message_dialog";
    if (id) {
        this.id = id;
    }
    this.idx = 0;
    this.icon_enabled = true;

    this.visible_id = "";

    this.input = null;
}

//should already be inside the DOM
MessageDialog.prototype.createContent = function () {
    this.idx++;

    let cnt = $("#" + this.id).clone();

    if (this.id) {
        this.visible_id = this.id + "_" + this.idx;
        cnt.attr("visibleID", this.visible_id);
        this.selector = "[visibleID="+this.visible_id+"]";
    }
    if (this.caption) {
        cnt.find(".Caption .Title").html(this.caption);
    }
    if (this.text) {
        cnt.find(".Inner .Contents .Text").html(this.text);
    }
    if (!this.icon_enabled) {
        cnt.find(".Contents .Icon").remove();

    }

    return cnt.get(0).outerHTML;
}

MessageDialog.prototype.setCaption = function (text) {
    this.caption = text;
}

MessageDialog.prototype.setText = function (text) {
    this.text = text;
}

//setup dialog to who the contents of DOM Element with ID=id
MessageDialog.prototype.setID = function (id) {
    this.id = id;
}

MessageDialog.prototype.show = function () {

    this.modal_pane.showContent(this.createContent());
    let buttonsBar = document.querySelector(this.selector + " .Buttons");
    //console.log(buttonsBar);
    buttonsBar.querySelectorAll("[action]").forEach(function(value,key,parent){
       //assign default actions
       value.addEventListener("click", function(event){
           event.stopPropagation();
           this.buttonAction(value.getAttribute("action"));
       }.bind(this));

    }.bind(this));

    this.modal_pane.paneClicked = function (event) {
        //console.log(event.target);

        if (event.target.className == this.modal_pane.className) {
            this.remove();
        }

    }.bind(this);

    let input = document.querySelector(this.selector + " INPUT");
    if (input) {
        input.focus();
        this.input = input;
    }

}
MessageDialog.prototype.remove = function() {
    this.modal_pane.pane().remove();
}

MessageDialog.prototype.buttonAction = function(action) {
    console.log("MessageDialog::buttonAction() - " +action);
}