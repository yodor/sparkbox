<?php
include_once("input/renderers/InputField.php");

class SliderField extends InputField
{

    public $accepted_values = NULL;

    protected function processInputAttributes()
    {

        parent::processInputAttributes();
        $field_value = htmlentities(mysql_real_unescape_string($this->input->getValue()), ENT_QUOTES, "UTF-8");
        $this->setInputAttribute("type", "hidden");
        $this->setInputAttribute("value", $field_value);

        if (is_array($this->accepted_values)) {
            $this->setInputAttribute("increments", implode("|", $this->accepted_values));
            $this->setInputAttribute("min", 0);
            $this->setInputAttribute("max", count($this->accepted_values) - 1);
        }

    }

    protected function renderImpl()
    {

        $attrs = $this->prepareInputAttributes();

        echo "<input $attrs>";

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

        $field_value = htmlentities(mysql_real_unescape_string($this->input->getValue()), ENT_QUOTES, "UTF-8");

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var name = "<?php echo $this->input->getName();?>";
                if ($("input[name='" + name + "'] + .slider_input").value) {
                    $("input[name='" + name + "'] + .slider_input").value("<?php echo $field_value;?>");
                }
            });
        </script>
        <?php
    }

}

?>