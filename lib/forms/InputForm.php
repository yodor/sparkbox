<?php
include_once("input/DataInputFactory.php");
include_once("beans/IBeanEditor.php");

class InputGroup extends SparkObject {


    /**
     * @var string Description of this input group
     */
    protected string $description = "";

    /**
     * @var array Names of all inputs in this input group
     */
    protected array $contents = array();

    public function __construct(string $name, string $description="")
    {
        parent::__construct();

        $this->contents = array();

        $this->setName($name);
        $this->setDescription($description);

    }

    public function containsInput(DataInput $input): bool
    {
        return array_key_exists($input->getName(), $this->contents);
    }

    public function inputNames() : array
    {
        return array_keys($this->contents);
    }

    public function addInput(DataInput $input): void
    {
        $this->contents[$input->getName()] = 1;
    }
    public function insertInputAfter(DataInput $input, string $after_name): void
    {

        $names = array_keys($this->contents);
        $index = (int)array_search($after_name, $names);
        $index++;


        // Create a new array with the new element at the desired position
        $this->contents = array_merge(
            array_slice($this->contents, 0, $index),
            array($input->getName() => 1),
            array_slice($this->contents, $index));
    }

    public function removeInput(DataInput $input): void
    {
        if ($this->containsInput($input)) {
            unset($this->contents[$input->getName()]);
        }
    }

    public function removeAll(): void
    {
        $this->contents = array();
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}
/**
 * Class InputForm
 * Generic HTML "Form" implementation
 * - Collection of "InputField" components
 * - Can be assigned with "FormRenderer" to render this form
 * - Can be assigned with "FormProcessor" to process the collection of InputFeilds
 * - Editor for DBTableBean class, and row ID can be set
 *
 * SparkBox Form handling usage:
 * After constructing SparkPage(or one of its subclasses) instance and before SitePage->begin() is executed
 * IFromProcessors->process is called with instance of InputForm
 * after SitePage->begin() is called which marks end of server side processing and start of rendering
 * form can be rendered using IFormRenderer->renderForm()
 */
class InputForm extends SparkObject implements IBeanEditor
{
    /**
     * @var bool
     */
    public $star_required = TRUE;

    /**
     * Associative array with key = DataInput name and value = DataInput object
     * @var array
     */
    protected array $inputs = array();

    /**
     * @var DBTableBean
     */
    protected ?DBTableBean $bean = NULL;
    /**
     * @var int
     */
    protected int $beanID = -1;

    /**
     * @var IFormProcessor
     */
    protected $processor = NULL;

    /**
     * @var FormRenderer
     */
    protected $renderer = NULL;


    const string DEFAULT_GROUP = "default";

    //group names
    protected array $groups = array();

    protected InputGroup $default_group;
    /**
     * InputForm constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->bean = NULL;
        $this->beanID = -1;

        $this->name = get_class($this);

        //create default group
        $this->default_group = new InputGroup(InputForm::DEFAULT_GROUP, "Default Group");
        $this->groups[$this->default_group->getName()] = $this->default_group;
    }

    public function addGroup(InputGroup $group): void
    {
        if (strcmp($group->getName(), InputForm::DEFAULT_GROUP)==0) {
            throw new Exception("InputGroup name '".InputForm::DEFAULT_GROUP."' is reserved");
        }
        $this->groups[$group->getName()] = $group;
    }

    public function insertGroupAfter(InputGroup $group, string $after_name): void
    {

        $index = array_search($after_name, array_keys($this->groups));

        $this->groups = array_slice($this->groups, 0, $index + 1, TRUE) + array($group->getName() => $group) + array_slice($this->groups, $index + 1, count($this->groups) - 1, TRUE);

    }

    public function getGroupNames(): array
    {
        return array_keys($this->groups);
    }

    public function getGroup(string $name): InputGroup
    {
        if (!array_key_exists($name, $this->groups)) throw new Exception("InputGroup name not found");

        return $this->groups[$name];
    }

    /**
     * Return the InputGroup object of DataInput $input
     * @param DataInput $input
     * @return InputGroup|null
     */
    public function getInputGroup(DataInput $input) : ?InputGroup
    {
        $result = NULL;

        foreach ($this->groups as $groupName=>$inputGroup)
        {
            if (!($inputGroup instanceof InputGroup))continue;

            if ($inputGroup->containsInput($input)) {
                $result = $inputGroup;
                break;
            }
        }

        return $result;
    }

    /**
     * Return the name of the InputGroup object this DataInput $input is part of
     * Non group objects are part of the 'Default group' specified with name InputForm::DEFAULT_GROUP
     * @param DataInput $input
     * @return string The group name this DataInput is part of
     */
    public function groupName(DataInput $input) : string
    {
        $result = NULL;

        foreach ($this->groups as $groupName=>$inputGroup)
        {
            if (!($inputGroup instanceof InputGroup))continue;

            if ($inputGroup->containsInput($input)) {
                $result = $inputGroup->getName();
                break;
            }

        }

        return $result;
    }

    public function getRenderer(): ?FormRenderer
    {
        return $this->renderer;
    }

    public function setRenderer(FormRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function setProcessor(IFormProcessor $processor): void
    {
        $this->processor = $processor;
    }

    public function getProcessor(): ?IFormProcessor
    {
        return $this->processor;
    }

    public function addInput(DataInput $input, InputGroup $group = NULL): void
    {
        if (is_null($group)) $group = $this->default_group;

        $input->setForm($this);
        $this->inputs[$input->getName()] = $input;

        $group->addInput($input);

    }

    public function setInputGroup(DataInput $input, InputGroup $group): void
    {
        $groupName = $this->groupName($input);
        $inputGroup = $this->getGroup($groupName);
        $inputGroup->removeInput($input);
        $group->addInput($input);
    }

    public function insertInputAfter(DataInput $input, string $after_name): void
    {

        $afterInput = $this->getInput($after_name);

        $input->setForm($this);

        $index = (int)array_search($after_name, array_keys($this->inputs));
        $index++;

        // Create a new array with the new element at the desired position
        $this->inputs = array_merge(
            array_slice($this->inputs, 0, $index),
            array($input->getName() => $input),
            array_slice($this->inputs, $index));

        $groupName = $this->groupName($afterInput);

        $inputGroup = $this->getGroup($groupName);
        $inputGroup->insertInputAfter($input, $after_name);

    }

    public function removeInput(string $name): void
    {
        $input = $this->getInput($name);
        $groupName = $this->groupName($input);

        $inputGroup = $this->getGroup($groupName);
        $inputGroup->removeInput($input);
        unset($this->inputs[$name]);
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function setEditID(int $editID): void
    {
        $this->beanID = $editID;
    }

    public function getEditID(): int
    {
        return $this->beanID;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function haveInput(string $name): bool
    {
        return array_key_exists($name, $this->inputs);
    }

    /**
     * @param string $name
     * @return DataInput
     * @throws Exception
     */
    public function getInput(string $name): DataInput
    {
        return $this->inputs[$name];
    }

    // 	public function getValuesArray()
    public function inputValues() : array
    {
        $ret = array();

        foreach ($this->inputs as $name => $input) {
            $ret[$name] = $input->getValue();
        }
        return $ret;
    }
    public function inputNames(): array
    {
        return array_keys($this->inputs);
    }

    /**
     * @return array
     */
    public function inputs(): array
    {
        return $this->inputs;
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

    public function clear(): void
    {
        foreach ($this->inputs as $name => $input) {
            $input->clear();
        }
    }

    public function removeAll(): void
    {
        $this->inputs = array();
    }

    /**
     * Validate all DataInputs in this collection
     * @throws Exception
     */
    public function validate(): void
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
     * Load and populate values into all inputs using their processor method loadPostData
     * @param array $data
     * @throws Exception
     */
    public function loadPostData(array $data) : void
    {

        $names = array_keys($this->inputs);

        foreach ($names as $pos => $name) {
            $input = $this->getInput($name);

            if ($input->isEditable()) {
                try {
                    $input->getProcessor()->loadPostData($data);
                }
                catch (Exception $e) {
                    $input->setError($e->getMessage());
                }
            }
        }

    }

    /**
     * Select bean data row by ID specified in $editID
     * Pass result to each of the inputs processors
     * @param int $editID
     * @param DBTableBean $bean
     * @return array
     * @throws Exception
     */
    public function loadBeanData(int $editID, DBTableBean $bean): array
    {

        debug("Loading data from '" . get_class($bean) . "' ID='$editID' ");

        //used
        $this->setBean($bean);
        $this->setEditID($editID);

        $result = array();

        if ($editID > 0) {
            debug("Edit/Update mode ");
            $result = $bean->getByID($editID);
            $item_key = $bean->key();

            //do not validate values coming from db

            debug("Loading form using bean '" . get_class($bean) . "' where " . $bean->key()." = ".$editID);

            //initial loading of bean data
            $names = $this->inputNames();
            foreach ($names as $pos => $name) {

                $input = $this->getInput($name);
                debug("Processing DataInput '$name'");

                //processor need value set. processor might need other values from the item_row or to parse differently the value
                $input->getProcessor()->loadBeanData($editID, $bean, $result);
            }

        }
        else {
            debug("Add/Insert mode");
        }
        return $result;
    }

    public function clearURLParameters(URL $url): void
    {
        $names = $this->inputNames();
        foreach ($names as $idx=>$name) {
            $input = $this->getInput($name);
            $input->getProcessor()->clearURLParameters($url);
        }
    }

    public function prepareClauseCollection(string $glue = SQLClause::DEFAULT_GLUE) : ClauseCollection
    {
        $where = new ClauseCollection();

        $names = $this->inputNames();
        foreach ($names as $pos => $name) {

            $input = $this->getInput($name);

            if ($input->skip_search_filter_processing) continue;

            $val = $input->getValue();

            if ($val > -1 && strcmp($val, "") != 0) {

                $field_name = str_replace("|", ".", $name);

                $clause = $this->clauseValue($field_name, $val);
                $clause->setGlue($glue);
                $where->append($clause);

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
        $names = $this->inputNames();

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

    public function renderPlain()
    {
        echo "<div class='FormValueList' name='".$this->getName()."'>";

        foreach ($this->inputs() as $index => $field) {
            if (!($field instanceof DataInput))continue;

            echo "<div class='item'>";
            echo "<label>" . tr($field->getLabel()) . ": </label>";
            $value = strip_tags(stripslashes((string)$field->getValue()));
            echo "<span>$value</span>";
            echo "</div>";
        }

        echo "</div>";
    }
}

?>
