<?php
include_once("input/processors/InputProcessor.php");

/**
 * Class CompoundInputProcessor
 * Read multiple posted input values into one DataInput value
 */
class CompoundInput extends InputProcessor
{

    const POST_CHAR = "_";

    protected string $concat_char = "-";
    protected array $compound_names = array();
    protected array $compound_values = array();

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->compound_values = array();

        //reset to default value -1
        foreach ($this->compound_names as $subname) {
            $this->compound_values[$subname] = -1;
        }
    }

    public function loadPostData(array $data) : void
    {

        $field_name = $this->input->getName();

        foreach ($this->compound_names as $subname) {
            $compound_name = $subname . "_" . $field_name; //ex for field birthdate => year_birthdate, month_birthdate, day_birthdate

            if (array_key_exists($compound_name, $data)) {
                $value = $data[$compound_name];

                $value = sanitizeInput($value);

                if (is_array($value)) {
                    $value = reorderArray($value);
                }

                $this->compound_values[$subname] = $value;
            }

        }

        //array case check. InputField can have checkbox
        if (is_array($this->compound_values[$this->compound_names[0]])) {

            $compound_count = count($this->compound_values[$this->compound_names[0]]);

            $arr_compound = array();

            for ($a = 0; $a < $compound_count; $a++) {

                $compound = array();
                foreach ($this->compound_names as $val) {

                    $compound[] = $this->compound_values[$val][$a];
                }
                $arr_compound[] = implode($this->concat_char, $compound);

            }

            $this->input->setValue($arr_compound);

        }
        else {

            $this->input->setValue(implode($this->concat_char, $this->compound_values));

        }

    }

    public function clearURLParameters(URL $url)
    {
        parent::clearURLParameters($url);

        $url->remove($this->input->getName());
        foreach ($this->compound_names as $val) {
            $url->remove($val."_".$this->input->getName());
        }
    }
}

?>
