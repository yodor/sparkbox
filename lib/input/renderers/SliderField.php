<?php
include_once("input/renderers/InputFieldTag.php");

class SliderField extends InputFieldTag
{

    public array $accepted_values = array();

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->input->setType("hidden");
    }


    protected function processAttributes() : void
    {

        parent::processAttributes();

        if (is_array($this->accepted_values)) {
            $this->input->setAttribute("increments", implode("|", $this->accepted_values));
            $this->input->setAttribute("min", 0);
            $this->input->setAttribute("max", count($this->accepted_values) - 1);
        }

    }

    protected function renderImpl()
    {

        parent::renderImpl();

        echo "<div class='slider_input'></div>";
        if (is_array($this->accepted_values)) {
            echo "<table width=100% cellpadding=0 cellspacing=0 style='border-collapse:collapse;'>";
            echo "<tr>";
            $pos = 1;
            $mid = round((count($this->accepted_values)) / 2.0);

            foreach ($this->accepted_values as $key => $val) {
                $align = "center";
                if ($pos < $mid) $align = "left";
                if ($pos > $mid) $align = "right";
                echo "<td align=$align style='width:" . (int)(100 / count($this->accepted_values)) . "%;'>$val</td>";
                $pos++;
            }
            echo "</tr>";
            echo "</table>";
        }

        $field_value = $this->dataInput->getValue();

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var name = "<?php echo $this->dataInput->getName();?>";
                if ($("input[name='" + name + "'] + .slider_input").value) {
                    $("input[name='" + name + "'] + .slider_input").value("<?php echo $field_value;?>");
                }
            });
        </script>
        <?php
    }

}

?>