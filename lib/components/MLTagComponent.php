<?php
include_once("lib/components/Component.php");

class MLTagComponent extends Component {

    protected $tag = NULL;
    
    public function __construct($tag_name="DIV") {
        $this->tag = $tag_name;
    }

    protected function renderImpl()
    {
        //
    }

    public function startRender()
    {
        $attrs = $this->prepareAttributes();
        echo "<$this->tag $attrs>";
    }

    public function finishRender()
    {
        echo "</$this->tag>";
    }
}

?>
