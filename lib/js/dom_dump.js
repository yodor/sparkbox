var d1;

/* Dump the DOM into a new window. */

function dump_dom ()
{
    if (typeof(Node) == "undefined") {
        alert ("Sorry, this script doesn't work with Internet Explorer.");
        return;
    }
    var w1 = window.open ("", 'DOM_output');
    d1 = w1.document; 
    d1.open(); 
    d1.writeln ('<h1>Tree of nodes</h1>\n<ol>');
    traverse_nodes (document, '', 1);
    d1.writeln('</ol>');
    d1.close();
}

/* Possible types of nodes. */

var node_types = new Array (
    "FAKE NODE", // fix array offset
    "ELEMENT NODE",
    "ATTRIBUTE NODE",
    "TEXT NODE",
    "CDATA SECTION NODE",
    "ENTITY REFERENCE NODE",
    "ENTITY NODE",
    "PROCESSING INSTRUCTION NODE",
    "COMMENT NODE",
    "DOCUMENT NODE",
    "DOCUMENT TYPE NODE",
    "DOCUMENT FRAGMENT NODE",
    "NOTATION NODE"
);

/* Write out a value in a certain colour, if the value exists,
   otherwise do nothing. */

function write_value_colour (value, value_name, colour)
{
    if (value) {
        var safe_value = 
            value.replace (/\n/g,'\\n').
                  replace (/</g, "&lt;").
                  replace (/>/g, '&gt;');
        d1.write(' <small>'+value_name+':</small> <font color="'+
                 colour+'">'+safe_value+'</font>');
    }
}

/* Traverse the sub-nodes of 'node' */

function traverse_nodes (node, num) 
{
    if (node.nodeType == Node.ELEMENT_NODE)
        d1.write('<li><b>&lt;'+node.nodeName+'&gt;</b>');
    else
        d1.write('<li><b>'+node.nodeName+'</b>');
    // Write the node's ID, class name, and value if they exist.
    write_value_colour (node.id,        "id",    "red");
    write_value_colour (node.className, "class", "green");
    write_value_colour (node.nodeValue, "value", "purple");
    // If the node is not a text node or an element node, print its
    // type.
    if (node.nodeType != Node.TEXT_NODE &&
        node.nodeType != Node.ELEMENT_NODE) {
        var node_type = node_types[node.nodeType].toLowerCase ();
        d1.write(' <i>('+node_type+')</i> ');
    }
    if (node.attributes && node.attributes.length) { 
        // Write the node's attributes, numbered A, B, C.
        d1.write('<ol type="A"> ')
        for (var i = 0; i < node.attributes.length; ++i)
            traverse_nodes (node.attributes.item(i),i);
        d1.write('</ol>')
    }
    if (node.childNodes && node.childNodes.length) { 
        // Write the node's child nodes, numbered 1, 2, 3.
        d1.write('<ol>')
        for (var i = 0; i < node.childNodes.length; ++i)
            traverse_nodes (node.childNodes.item(i), i);
        d1.write('</ol>')
    }
    d1.write("</li>\n");
}