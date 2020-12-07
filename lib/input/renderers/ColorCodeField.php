<?php
include_once("input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{
    /**
     * @var string
     */
    protected $chooser_name = "";

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->chooser_name = $input->getName()."_chooser";
    }

    protected function processInputAttributes()
    {

        parent::processInputAttributes();

        $this->setInputAttribute("type", "text");
        $this->setInputAttribute("onChange", "this.form.{$this->chooser_name}.value=this.value;");

    }

    public function renderImpl()
    {
        echo "<input type='color' name='{$this->chooser_name}' onChange='this.form.".$this->input->getName().".value=this.value;'>";
        parent::renderImpl();
?>
        <script type="text/javascript">
            onPageLoad(function(){
                let input = document.querySelector('input[name=<?php echo $this->input->getName();?>');
                input.onchange({target: input});
            });
            </script>
<?php
    }

}

?>