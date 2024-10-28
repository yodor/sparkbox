class TreeView extends Component {

    static BRANCH_OPENED = "opened";
    static BRANCH_CLOSED = "closed";
    static BRANCH_LEAF = "leaf";


    constructor() {
        super();
        this.setClass(".TreeView");
    }



    initialize() {

        super.initialize();

        this.element.querySelectorAll(".Node").forEach((node)=>{

            const nodeID = node.getAttribute("nodeID");
            const branchType = node.getAttribute("branch_type");

            node.addEventListener("click", (event) => this.nodeClicked(nodeID));

            const handle = node.querySelector(".Handle");
            handle.addEventListener("click", (event) => this.handleClicked(nodeID));



        });

        //reset leaf branches because of aggregate select and/or cached treeview
        this.element.querySelectorAll(".NodeChilds:empty").forEach((node) =>{
            node.closest(".NodeOuter")?.querySelector(".Node")?.setAttribute("branch_type", "leaf");
        });

    }

    /**
     * Set node active and open all branches towards the root of the tree
     * @param nodeID {string}
     */
    setSelectedID(nodeID) {

        //console.log("selecting ID: " + nodeID);
        if (parseInt(nodeID)<1) {
            //console.log("Nothing to select");
            return;
        }

        //close all open branches
        this.element.querySelectorAll(".Node[branch_type='opened']").forEach((node_element)=>{
            node_element.setAttribute("branch_type", "closed");
        });

        //set the active node to not active
        this.element.querySelectorAll(".Node[active='1']").forEach((node_element)=>{
            node_element.setAttribute("active", "0");
        })

        const selectedNode = this.element.querySelector(".Node[nodeID='"+nodeID+"']");
        if (selectedNode == null) return;

        selectedNode.setAttribute("active", "1");

        if (selectedNode.getAttribute("branch_type") !== "leaf") {
            selectedNode.setAttribute("branch_type", "opened");
        }

        let currentNode = selectedNode;

        //walk up until tree root
        while (currentNode) {
            //console.log("Current nodeID: " + currentNode.getAttribute("nodeID"));
            let parent = currentNode?.closest(".NodeChilds")?.closest(".NodeOuter")?.querySelector(".Node");
            if (parent) {
                //console.log("Parent nodeID: " + parent.getAttribute("nodeID"));
                parent.setAttribute("branch_type", "opened");
            }
            currentNode = parent;
        }

    }

    /**
     * node handle button click handler
     * @param nodeID {string}
     */
    handleClicked(nodeID) {

        const node = this.element.querySelector(".Node[nodeID='"+nodeID+"']");

        const branchType = node.getAttribute("branch_type");

        if (branchType === "opened") {
            //close the branch
            node.setAttribute("branch_type", "closed");

            //close all child branches
            const parent = node.closest(".NodeOuter");
            parent.querySelectorAll(".Node[branch_type='opened']").forEach((node_element)=>{
                node_element.setAttribute("branch_type", "closed");
            });

        } else if (branchType === "closed") {
            //open the current branch
            node.setAttribute("branch_type", "opened");

        } else {
            //lead node do nothing
        }

    }


    /**
     * Default delegate handler for click on .Node
     * @param nodeID {string}
     */
    nodeClicked(nodeID) {

    }

}
