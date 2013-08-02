
function TreeView()
{
    this.component_class = ".TreeView";
    
   
    this.cls = this.component_class;
    

}
TreeView.prototype.attachWith = function(name)
{
    if (name)
    {
      this.cls = this.component_class + "[name='"+name+"']";
      
    }
    var tree_instance = this;


    $(this.cls + " .Node").each(function(index) {

	    var nodeID = $(this).attr("nodeID");

	    $(this).click(function(event){

		tree_instance.nodeClicked(nodeID, event);

	    });

	    var handle = $(this).find(".Handle").first();
	    handle.click(function(event){

		tree_instance.handleClicked(nodeID, event);

	    });
	    
	    //update leaf nodes for aggregate function
	    var child_count = $(this).parent().children(".NodeChilds").first().children().length;
// 	    console.log(child_count);
	    if (child_count < 1) {
	      $(this).attr("branch_type", "leaf");
	    }
	    
    });
    
   
    
    //open the active branch to top
    $(this.cls + " .Node[active='1']").each(function(){
	    var nodeID = $(this).attr("nodeID");

// 	    console.log("Active NodeID: "+nodeID);

	    $(this).parents(".NodeOuter").each(function(){
	    
		$(this).children(".Node").each(function(){
		    var nodeID = $(this).attr("nodeID");
		    var branch_type = $(this).attr("branch_type");
		    
// 		    console.log("Checking NodeID: "+nodeID + " | BranchType: "+branch_type);

		    if (nodeID>0 && branch_type=="closed") {
		      
		      tree_instance.handleClicked(nodeID);
		      
		    }
		  
		});
		
	      
	    });
      
    });
  
}
TreeView.prototype.handleClicked = function(id, event)
{
//     console.log("NodeID: "+id);
    
    var node = $(this.cls + " .Node[nodeID='"+id+"']");
    var branch_type = node.attr("branch_type");
    console.log("Node Branch Type: " + branch_type);
    
    var current_branch_type = branch_type;
    
    var handle = node.find(".Handle").first();
   
    var childs = node.parent().find(".NodeChilds").first();
   
    if (branch_type == "opened") {
      //close the branch
      node.attr("branch_type", "closed");

      childs.find(".Node[branch_type='opened']").each(function(index){
	  $(this).attr("branch_type","closed");
	
      });
      current_branch_type = "closed";
    }
    else if (branch_type == "closed") {
      //open the branch
      node.attr("branch_type", "opened");
      current_branch_type = "opened";
    }
    else {
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

//reimplement nodeClicked to get notified when node is cliked
TreeView.prototype.nodeClicked = function(id, event)
{
    $.event.trigger({
      type: "TreeView",
      message: "onNodeClicked",
      time: new Date(),
      nodeID: id,
      source: this.source
    });
}