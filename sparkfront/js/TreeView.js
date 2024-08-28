class TreeView extends Component {
    constructor() {
        super();
        this.setClass(".TreeView");
    }

    setSelectedID(selectedID) {

        var tree_instance = this;

        //deselect any selected (from cache)
        $(this.selector() + " .Node").each(function () {
            $(this).attr("active", "0");
        });

        //open the active branch to top
        $(this.selector() + " .Node[nodeID='"+selectedID+"']").each(function () {

            $(this).parents(".NodeOuter").each(function () {

                $(this).children(".Node").each(function () {
                    var nodeID = $(this).attr("nodeID");
                    var branch_type = $(this).attr("branch_type");

                    if (nodeID > 0 && branch_type == "closed") {

                        tree_instance.handleClicked(nodeID);

                    }

                });


            });

        });

        $(this.selector() + " .Node[nodeID='"+selectedID+"']").each(function () {
            //var nodeID = $(this).attr("nodeID");
            $(this).attr("active", "1");
        });
    }

    initialize() {

        super.initialize();

        var tree_instance = this;

        $(this.selector() + " .Node").each(function (index) {

            var nodeID = $(this).attr("nodeID");

            $(this).click(function (event) {

                tree_instance.nodeClicked(nodeID, event);

            });

            var handle = $(this).find(".Handle").first();
            handle.click(function (event) {

                tree_instance.handleClicked(nodeID, event);

            });

            //update leaf nodes for aggregate function
            var child_count = $(this).parent().children(".NodeChilds").first().children().length;

            if (child_count < 1) {
                $(this).attr("branch_type", "leaf");
            }

        });


    }

    handleClicked(id, event) {

        var node = $(this.selector() + " .Node[nodeID='" + id + "']");
        var branch_type = node.attr("branch_type");
        //console.log("Node Branch Type: " + branch_type);

        var current_branch_type = branch_type;

        var handle = node.find(".Handle").first();

        var childs = node.parent().find(".NodeChilds").first();

        if (branch_type == "opened") {
            //close the branch
            node.attr("branch_type", "closed");

            childs.find(".Node[branch_type='opened']").each(function (index) {
                $(this).attr("branch_type", "closed");
            });
            current_branch_type = "closed";

        } else if (branch_type == "closed") {
            //open the branch
            node.attr("branch_type", "opened");
            current_branch_type = "opened";

        } else {
            //
        }

        $.event.trigger({
            type: "TreeView",
            message: "onHandleClicked",
            time: new Date(),
            nodeID: id,
            source: this.source,
            old_branch_type: branch_type,
            current_branch_type: current_branch_type,
        });

    }

    //reimplement nodeClicked to get notified when node is clicked
    nodeClicked(id, event) {
        $.event.trigger({
            type: "TreeView",
            message: "onNodeClicked",
            time: new Date(),
            nodeID: id,
            source: this.source
        });
    }

}
