<?php
include_once("input/renderers/InputField.php");

class TimeField extends InputField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->is_compound = true;

    }

    protected function renderImpl()
    {
        $field_value = $this->input->getValue();
        $field_name = $this->input->getName();

        $hour = -1;
        $minute = -1;
        if (strpos($field_value, ":") !== false) {
            list($hour, $minute) = explode(":", $field_value);
        }
        echo "<div class='FieldElements'>";

        echo "<select class='TimePart Hour' name=hour_{$field_name} >";

        echo "<option value=-1>--</option>";
        for ($d = 0; $d < 24; $d++) {
            $sel = "";

            if ((int)$d == (int)$hour) $sel = " SELECTED ";
            $z = "";
            if ($d < 10) $z = "0";
            echo "<option $sel value=$d>" . $z . $d . "</option>";
        }

        echo "</select>";

        echo "<select class='TimePart Minute'  name=minute_{$field_name} >";
        echo "<option value=-1>--</option>";
        for ($a = 0; $a < 60; $a++) {
            $sel = "";
            if ((int)$a == (int)$minute) $sel = " SELECTED ";
            $z = "";
            if ($a < 10) $z = "0";
            echo "<option $sel value=$a>" . $z . $a . "</option>";
        }

        echo "</select>";

        echo "</div>";
    }

}

?>