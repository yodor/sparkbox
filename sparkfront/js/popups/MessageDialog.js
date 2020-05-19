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
}

//should already be inside the DOM
MessageDialog.prototype.createContent = function () {
    this.idx++;

    let cnt = $("#" + this.id).clone();

    if (this.id) {
        this.visible_id = this.id + "_" + this.idx;
        cnt.attr("id", this.visible_id);
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

MessageDialog.prototype.setID = function (id) {
    this.id = id;
}

MessageDialog.prototype.show = function () {

    this.modal_pane.showContent(this.createContent());

}
MessageDialog.prototype.remove = function() {
    this.modal_pane.pane().remove();
}