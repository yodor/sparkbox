<?php
include_once("lib/input/DataInputFactory.php");
include_once("lib/beans/IDBTableEditor.php");


/**
 * Class InputForm
 * Generic HTML "Form" implementation
 * - Collection of "InputField" components
 * - Can be assigned with "FormRenderer" to render this form
 * - Can be assigned with "FormProcessor" to process the collection of InputFeilds
 * - Editor for DBTableBean class, and row ID can be set
 *
 * Sparkbox Form handling usage:
 * After constructing SitePage(or one of its subclasses) instance and before SitePage->begin() is exectued
 * IFromProcessors->processForm is called with instance of InputForm
 * after SitePage->begin() is called which marks end of server side processing and start of rendering
 *form can be rendered using IFormRenderer->renderForm()
 */
class InputForm implements IDBTableEditor
{
    /**
     * @var bool
     */
    public $star_required = TRUE;

    /**
     * @var array
     */
    protected $inputs = array();

    /**
     * @var DBTableBean
     */
    protected $bean = NULL;
    /**
     * @var int
     */
    protected $beanID = -1;

    /**
     * @var IFormProcessor
     */
    protected $processor = NULL;

    /**
     * @var FormRenderer
     */
    protected $renderer = NULL;

    public function __construct()
    {
        $this->bean = NULL;
        $this->beanID = -1;
    }

    public function getRenderer(): ?FormRenderer
    {
        return $this->renderer;
    }

    public function setRenderer(FormRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function setProcessor(IFormProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getProcessor(): ?IFormProcessor
    {
        return $this->processor;
    }

    public function addInput(DataInput $input)
    {
        $input->setForm($this);
        $this->inputs[$input->getName()] = $input;
    }

    public function insertFieldAfter(DataInput $input, string $after_name)
    {
        $index = array_search($after_name, array_keys($this->inputs));

        $this->inputs =
            array_slice($this->inputs, 0, $index + 1, TRUE) +
            array($input->getName() => $input) +
            array_slice($this->inputs, $index + 1, count($this->inputs) - 1, TRUE);

    }

    public function removeInput(string $name)
    {
        if (isset($this->inputs[$name])) {
            unset($this->inputs[$name]);
        }
    }

    public function setBean(DBTableBean $bean) : void
    {
        $this->bean = $bean;
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function setEditID(int $editid) : void
    {
        $this->beanID = $editid;
    }

    public function getEditID(): int
    {
        return $this->beanID;
    }

    /**
     * @param string $field_name
     * @return bool
     */
    public function haveInput(string $name) : bool
    {
        return array_key_exists($name, $this->inputs);
    }

    /**
     * @param string $field_name
     * @return DataInput
     * @throws Exception
     */
    public function getInput(string $name): DataInput
    {
        return $this->inputs[$name];
    }


    // 	public function getValuesArray()
    public function getInputValues()
    {
        $ret = array();

        foreach ($this->inputs as $name => $input) {
            $ret[$name] = $input->getValue();
        }
        return $ret;
    }

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getInputNames() : array
    {
        return array_keys($this->inputs);
    }

    public function valueUnescape(string $name)
    {
        $input = $this->getInput($name);
        return mysql_real_unescape_string($input->getValue());
    }

    /**
     * @return bool
     */
    public function haveErrors() : bool
    {
        $found_error = FALSE;
        foreach ($this->inputs as $name => $input) {
            if ($input->haveError() === TRUE) {
                $found_error = TRUE;
                break;
            }
        }
        return $found_error;
    }

    public function clear() : void
    {
        foreach ($this->inputs as $name => $input) {
            $input->clear();
        }
    }


    /**
     * Load values with data from _POST or DBTableBean
     * @param array $arr
     * @throws Exception
     */
    public function loadPostData(array $arr) : void
    {

        $names = array_keys($this->inputs);

        foreach ($names as $pos => $name) {
            $input = $this->getInput($name);

            if ($input->isEditable()) {
                $input->loadPostData($arr);
            }
        }

    }

    /**
     * Validate data values for all fields in this form
     * @throws Exception
     */
    public function validate()
    {
        $names = array_keys($this->inputs);

        foreach ($names as $pos => $name) {
            $input = $this->getInput($name);

            if ($input->isEditable()) {
                $input->validate();
            }
        }
    }

    public function loadBeanData(int $editID, DBTableBean $bean)
    {
        debug("Loading data from '" . get_class($bean) . "' ID='$editID' ");

        //TODO: check if setEditBean and editID is used anymore
        $this->setBean($bean);
        $this->setEditID($editID);

        $item_row = array();

        if ($editID > 0) {
            debug("Edit/Update mode ");
            $item_row = $bean->getByID($editID);
            $item_key = $bean->key();

            //do not validate values comming from db
            //$this->load($item_row);

            //initial loading of bean data
            $names = $this->getInputNames();
            foreach ($names as $pos => $name) {

                debug("Loading data for field name: $name");

                $input = $this->getInput($name);
                //processor need value set. processor might need other values from the item_row or to parse differently the value
                $input->getProcessor()->loadBeanData($editID, $bean, $input, $item_row);
            }

        }
        else {
            debug("Add/Insert mode");
        }
        return $item_row;
    }

    public function searchFilterArray()
    {
        $search_filter = array();


        foreach ($this->inputs as $name => $input) {

            if ($input->skip_search_filter_processing) continue;

            $val = $input->getValue();

            if ($val > -1 && strcmp($val, "") != 0) {

                $field_name = str_replace("|", ".", $name);

                $sffk = $this->searchFilterForKey($field_name, $val);
                if ($sffk) $search_filter[] = $sffk;
            }
        }
        return $search_filter;
    }

    protected function searchFilterForKey($key, $val)
    {
        return "$key='$val'";
    }

    public function searchFilter($type = " WHERE ")
    {
        $sa = $this->searchFilterArray();
        $sf = "";
        if (count($sa) > 0) {
            $sf = " $type " . implode(" AND ", $sa);
        }
        return $sf;
    }

    public function searchFilterSelect(): SQLSelect
    {
        $sel = new SQLSelect();
        $sel->fields = "";
        $sel->from = "";

        $sa = $this->searchFilterArray();
        $sf = "";
        if (count($sa) > 0) {
            $sf = implode(" AND ", $sa);
        }
        $sel->where = $sf;
        return $sel;
    }

    public function serializeXML()
    {
        ob_start();
        echo "<?xml version='1.0' encoding='utf-8'?>";
        echo "<inputform class='" . get_class($this) . "'>";
        echo "<fields>";
        foreach ($this->inputs as $name => $input) {
            echo "<field>";
            echo "<name>$name</name>";
            echo "<value><![CDATA[" . $input->getValue() . "]]></value>";
            echo "</field>";
        }
        echo "</fields>";
        echo "</inputform>";
        $xml = ob_get_contents();
        ob_end_clean();
        return $xml;
    }

    public function unserializeXML($xml_string)
    {
        ob_start();
        $inputform = simplexml_load_string($xml_string);
        $err = ob_get_contents();
        ob_end_clean();
        if (!$inputform) throw new Exception("Unable to parse input as XML: [$err]");

        foreach ($inputform->fields->field as $field) {
            $name = (string)$field->name;
            $value = (string)$field->value;
            // 		echo $name."=>".$value;
            if (!$this->haveInput($name)) continue;

            $this->getInput($name)->setValue($value);
        }


    }

    public function dumpErrors()
    {
        foreach ($this->inputs as $field_name => $field) {

            if ($field->haveError()) {
                echo "$field_name:";
                var_dump($field->getValue());
                echo "<HR>";
                echo "Error: ";
                var_dump($field->getError()) . "<BR>";
            }

        }
    }

    public function dumpForm()
    {

        foreach ($this->inputs as $name => $input) {
            if ($input instanceof PlainUpload) continue;
            echo $input->getLabel() . ": " . $input->getValue() . "<br><BR>\r\n\r\n";

        }
    }


}

?>
