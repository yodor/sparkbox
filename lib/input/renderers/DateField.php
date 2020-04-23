<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/input/validators/DateValidator.php");

class DateField extends InputField
{

    protected $y_start = -1;
    protected $y_end = -1;

    public $render_calendar = true;

    public $short_month = false;
    public $short_yaer = false;
    public $h_pos = "left";
    public $v_pos = "down";

    public function __construct()
    {
        parent::__construct();

    }

    public function setYearPeriod($y_start, $y_end)
    {
        $this->y_start = $y_start;
        $this->y_end = $y_end;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/CalendarPopup.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/CalendarPopup.js";
        return $arr;
    }

    public function renderImpl()
    {

        echo "<div class='FieldElements'>";

        $field_value = $this->field->getValue();
        $field_name = $this->field->getName();

        $pieces = explode("-", $field_value);


        $year = -1;
        $month = -1;
        $day = -1;

        if (count($pieces) == 3) {
            $year = $pieces[0];
            $month = $pieces[1];
            $day = $pieces[2];
        }


        if ($this->y_start == -1) {
            $this->y_start = date("Y") - 30;
            $this->y_end = date("Y") + 30;
        }
        if ($this->y_end == -1) {
            $this->y_end = $this->y_start + 30;
        }


        echo "<select name='day_{$field_name}' class='DatePart Day'>";
        echo "<option value=''>--</option>";
        for ($d = 1; $d < 32; $d++) {
            $sel = "";

            if ((int)$d == (int)$day) $sel = " SELECTED ";
            echo "<option $sel value='$d'>" . $d . "</option>";
        }

        echo "</select>";

        $months_full = array();

        for ($a = 1; $a <= 12; $a++) {
            $months_full[] = date("M", strtotime("1-$a-" . date("Y")));
        }

        $months = $months_full;


        echo "<select name='month_{$field_name}' class='DatePart Month'>";
        echo "<option value=''>--</option>";
        for ($a = 0; $a < count($months); $a++) {
            $sel = "";
            $m = ($a + 1);
            if (strcmp((int)$m, (int)$month) == 0) $sel = " SELECTED ";
            echo "<option $sel value='$m'>" . tr($months[$a]) . "</option>";
        }
        echo "</select>";

        echo "<select name='year_{$field_name}' class='DatePart Year'>";
        echo "<option value=''>--</option>";
        for ($a = $this->y_start; $a < $this->y_end; $a++) {
            $sel = "";
            if (strcmp($a, $year) == 0) $sel = " SELECTED ";
            echo "<option $sel value='$a'>$a</option>";
        }
        echo "</select>";

        if ($this->render_calendar) {
            // 	calendar_popup_button
            echo "<div class='CalendarControl' onClick='javascript:new CalendarPopup(this)'>";
            echo "<img border=0  src='" . SITE_ROOT . "lib/images/calendar_icon.png'>";
            echo "</div>";
        }
        echo "</div>";
    }

    public function renderValueImpl()
    {
        $field_value = $this->field->getValue();

        if (DateValidator::isValidDate($field_value)) {
            $pieces = explode("-", $field_value);

            $year = $pieces[0];
            $month = $pieces[1];
            $day = $pieces[2];

            echo date("M, d Y", strtotime("$year-$month-$day"));
        }
        else {
            echo "-";
        }
    }
}

?>