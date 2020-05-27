<?php
include_once("input/DataInputFactory.php");
include_once("beans/IBeanEditor.php");

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
class InputForm implements IBeanEditor
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

    protected $name = "";

    /**
     * InputForm constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->bean = NULL;
        $this->beanID = -1;
        $this->name = get_class($this);
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        if ($this->name) return $this->name;
        return get_class($this);
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

        $this->inputs = array_slice($this->inputs, 0, $index + 1, TRUE) + array($input->getName() => $input) + array_slice($this->inputs, $index + 1, count($this->inputs) - 1, TRUE);

    }

    public function removeInput(string $name)
    {
        if (isset($this->inputs[$name])) {
            unset($this->inputs[$name]);
        }
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function setEditID(int $editid)
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
    public function haveInput(string $name): bool
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

    public function getInputNames(): array
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
    public function haveErrors(): bool
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

    public function clear()
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
    public function loadPostData(array $arr)
    {

        $names = array_keys($this->inputs);

        foreach ($names as $pos => $name) {
            $input = $this->getInput($name);

            if ($input->isEditable()) {
                $input->getProcessor()->loadPostData($arr);
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

    /**
     * Select bean data row by ID specified in $editID
     * Pass result to each of the inputs processors
     * @param int $editID
     * @param DBTableBean $bean
     * @return array|mixed
     * @throws Exception
     */
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

            //do not validate values coming from db

            //initial loading of bean data
            $names = $this->getInputNames();
            foreach ($names as $pos => $name) {

                $input = $this->getInput($name);
                debug("Processing DataInput '$name'");
                //processor need value set. processor might need other values from the item_row or to parse differently the value
                $input->getProcessor()->loadBeanData($editID, $bean, $item_row);
            }

        }
        else {
            debug("Add/Insert mode");
        }
        return $item_row;
    }

    public function clearURLParameters(URLBuilder $url)
    {
        $names = $this->getInputNames();
        foreach ($names as $idx=>$name) {
            $input = $this->getInput($name);
            $input->getProcessor()->clearURLParameters($url);
        }
    }

    public function prepareClauseCollection(string $glue = SQLClause::DEFAULT_GLUE) : ClauseCollection
    {
        $where = new ClauseCollection();

        $names = $this->getInputNames();
        foreach ($names as $pos => $name) {

            $input = $this->getInput($name);

            if ($input->skip_search_filter_processing) continue;

            $val = $input->getValue();

            if ($val > -1 && strcmp($val, "") != 0) {

                $field_name = str_replace("|", ".", $name);

                $clause = $this->clauseValue($field_name, $val);
                $clause->setGlue($glue);
                $where->addClause($clause);

            }
        }
        return $where;
    }

    protected function clauseValue(string $field_name, string $val): SQLClause
    {
        $clause = new SQLClause();
        $clause->setExpression($field_name, $val, "=");
        return $clause;
    }

    /**
     * Serialize all datainputs in this form as XML string
     * @return string
     * @throws Exception
     */
    public function serializeXML() : string
    {
        ob_start();
        echo "<?xml version='1.0' encoding='utf-8'?>";
        echo "<inputform class='" . get_class($this) . "'>";
        echo "<fields>";
        $names = $this->getInputNames();

        foreach ($names as $idx => $name) {
            $input = $this->getInput($name);
            echo "<field>";
            echo "<name>" . $input->getName() . "</name>";
            echo "<value><![CDATA[" . $input->getValue() . "]]></value>";
            echo "</field>";
        }
        echo "</fields>";
        echo "</inputform>";
        $xml = ob_get_contents();
        ob_end_clean();
        return $xml;
    }

    /**
     * Unserialize the datainputs values from the XML string
     * @param $xml_string string
     * @throws Exception
     */
    public function unserializeXML(string $xml_string)
    {
        ob_start();
        $inputform = simplexml_load_string($xml_string);
        $err = ob_get_contents();
        ob_end_clean();
        if (!$inputform) throw new Exception("Unable to parse input as XML: [$err]");

        foreach ($inputform->fields->field as $field) {
            $name = (string)$field->name;
            $value = (string)$field->value;

            if (!$this->haveInput($name)) continue;

            $this->getInput($name)->setValue($value);
        }

    }

}

?>
