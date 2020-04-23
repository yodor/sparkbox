<?php
include_once("lib/input/renderers/InputField.php");

class SliderField extends InputField
{

    public $accepted_values = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->setClassName("slider_field");

    }
    //   public function startRender()
    //   {
    // 	  echo "<div class='slider_field_wrapper'>";
    //   }
    //   public function finishRender()
    //   {
    // 	  echo "</div>";
    //   }
    public function renderImpl()
    {

        $field_value = $this->field->getValue();
        $field_name = $this->field->getName();

        $field_value = htmlentities(mysql_real_unescape_string($field_value), ENT_QUOTES, "UTF-8");
        $this->attributes["type"] = "hidden";
        $this->attributes["value"] = $field_value;
        $this->attributes["name"] = $field_name;

        if (is_array($this->accepted_values)) {
            $this->attributes["increments"] = implode("|", $this->accepted_values);
            $this->attributes["min"] = 0;
            $this->attributes["max"] = count($this->accepted_values) - 1;
        }

        $attrs = $this->prepareAttributes();

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

        ?>
        <script type='text/javascript'>
            addLoadEvent(function () {
                var name = "<?php echo $field_name;?>";
                if ($("input[name='" + name + "'] + .slider_input").value) {
                    $("input[name='" + name + "'] + .slider_input").value("<?php echo $field_value;?>");
                }
            });
        </script>
        <?php
    }

}

?>