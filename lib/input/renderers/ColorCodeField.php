<?php
include_once("input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{

    protected Input $chooser;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->input->setType("text");

        $this->chooser = new Input();
        $this->chooser->setType("color");
        $this->chooser->setName($input->getName()."_chooser");
        $this->items()->prepend($this->chooser);
    }

    public function colorChooser() : Input
    {
        return $this->chooser;
    }

    protected function processAttributes() : void
    {

        parent::processAttributes();

        $this->chooser->processAttributes();

        $chooser_name = $this->chooser->getName();
        $this->input->setAttribute("onChange", "this.form.{$chooser_name}.value=this.value;");

        $data_name = $this->dataInput->getName();
        $this->chooser->setAttribute("onChange","this.form.{$data_name}.value=this.value;");

    }

    public function renderImpl()
    {
        parent::renderImpl();
?>
        <script type="text/javascript">
            onPageLoad(function(){
                let input = document.querySelector('input[name=<?php echo $this->dataInput->getName();?>');
                input.onchange({target: input});
            });
            </script>
<?php
    }

}

?>
