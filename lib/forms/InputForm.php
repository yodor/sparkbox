<?php
include_once("lib/input/DataInput.php");
include_once("lib/beans/IDataBeanGetter.php");
include_once("lib/beans/IDataBeanSetter.php");

/**
 * Class InputForm
 * Generic HTML "Form" implementation
 * - Collection of "InputField" components
 * - Can be assigned with "IFormRenderer" to render this form
 * - Can be assigned with "IFormProcessor" to process the collection of InputFeilds
 * - Editor for DBTableBean class, and row ID can be set
 *
 * Sparkbox Form handling usage:
 * After constructing SitePage(or one of its subclasses) instance and before SitePage->begin() is exectued
 * IFromProcessors->processForm is called with instance of InputForm
 * after SitePage->begin() is called which marks end of server side processing and start of rendering
 *form can be rendered using IFormRenderer->renderForm()
 */
class InputForm implements IDataBeanSetter, IDataBeanGetter
{
    /**
     * @var bool
     */
    public $star_required = true;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var IDataBean
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
     * @var IFormRenderer
     */
    protected $renderer = NULL;

    public function __construct()
    {
        $this->bean = NULL;
        $this->beanID = -1;
    }

    public function getRenderer()
    {
        return $this->renderer;
    }

    public function setRenderer(IFormRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function setProcessor(IFormProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getProcessor()
    {
        return $this->processor;
    }

    public function addField(DataInput $field)
    {
        $field->setForm($this);
        $this->fields[$field->getName()] = $field;
    }

    public function insertFieldAfter(DataInput $field, $after_field_name)
    {

        $keys = array_keys($this->fields);
        $index = array_search($after_field_name, $keys);

        $field_name = $field->getName();


        $this->fields = array_slice($this->fields, 0, $index + 1, true) + array("$field_name" => $field) + array_slice($this->fields, $index + 1, count($this->fields) - 1, true);

    }

    public function removeField($field_name)
    {
        if (isset($this->fields[$field_name])) {
            unset($this->fields[$field_name]);
        }
    }

    public function setBean(IDataBean $bean)
    {
        $this->bean = $bean;
    }

    public function getBean()
    {
        return $this->bean;
    }

    public function setEditID(int $editid)
    {
        $this->beanID = $editid;
    }

    public function getEditID()
    {
        return $this->beanID;
    }

    /**
     * @param string $field_name
     * @return bool
     */
    public function haveField(string $field_name)
    {
        return array_key_exists($field_name, $this->fields);
    }

    /**
     * @param string $field_name
     * @return mixed
     * @throws Exception
     */
    public function fieldExists(string $field_name)
    {
        if (!$this->haveField($field_name)) throw new Exception("InputField [$field_name] is not defined in this form: " . get_class());
        return $this->fields[$field_name];
    }


    /**
     * @param string $field_name
     * @return mixed
     * @throws Exception
     */
    public function getField(string $field_name)
    {
        return $this->fieldExists($field_name);
    }


    // 	public function getValuesArray()
    public function getFieldValues()
    {
        $ret = array();

        foreach ($this->fields as $field_name => $field) {
            $ret[$field_name] = $field->getValue();
        }
        return $ret;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    public function valueUnescape($field_name)
    {
        $field = $this->fieldExists($field_name);
        return mysql_real_unescape_string($field->getValue());
    }

    /**
     * @return bool
     */
    public function haveErrors()
    {
        $found_error = false;
        foreach ($this->fields as $field_name => $field) {
            if ($field->haveError() === true) {
                $found_error = true;
                break;
            }
        }
        return $found_error;
    }

    public function clear()
    {
        foreach ($this->fields as $field_name => $field) {
            $field->clear();
        }
    }


    public function loadPostData(array $arr)
    {
        foreach ($this->fields as $field_name => $field) {
            if ($field->isEditable()) {
                $field->loadPostData($arr);

            }
        }

    }

    /**
     *
     */
    public function validate()
    {
        foreach ($this->fields as $field_name => $field) {

            if ($field->isEditable()) {
                $field->validate();
            }
        }
    }

    public function loadBeanData($editID, DBTableBean $bean)
    {
        debug("InputForm::loadBeanData: editID='$editID' " . get_class($bean));

        //TODO: check if setEditBean and editID is used anymore
        $this->setBean($bean);
        $this->setEditID($editID);

        $item_row = array();

        if ($editID > 0) {
            debug("InputForm::loadBeanData: Edit/Update mode ");
            $item_row = $bean->getByID($editID);
            $item_key = $bean->key();

            //do not validate values comming from db
            //$this->load($item_row);


            //initial loading of bean data
            foreach ($this->fields as $field_name => $field) {
                debug("InputForm::loadBeanData: loading field: $field_name");

                //processor need value set. processor might need other values from the item_row or to parse differently the value
                $field->getProcessor()->loadBeanData($editID, $bean, $field, $item_row);
            }

        }
        else {
            debug("InputForm::loadBeanData: Add/Insert mode ");
        }
        return $item_row;
    }

    public function searchFilterArray()
    {
        $search_filter = array();


        foreach ($this->fields as $field_name => $field) {

            if ($field->skip_search_filter_processing) continue;

            $val = $field->getValue();

            if ($val > -1 && strcmp($val, "") != 0) {

                $field_name = str_replace("|", ".", $field_name);

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

    public function searchFilterQuery()
    {
        $sel = new SelectQuery();
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
        foreach ($this->fields as $field_name => $field) {
            echo "<field>";
            echo "<name>$field_name</name>";
            echo "<value><![CDATA[" . $field->getValue() . "]]></value>";
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
            if (!$this->haveField($name)) continue;

            $this->getField($name)->setValue($value);
        }


    }

    public function dumpErrors()
    {
        foreach ($this->fields as $field_name => $field) {

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

        foreach ($this->fields as $field_name => $field) {
            if ($field instanceof UploadField) continue;
            echo $field->getLabel() . ": " . $field->getValue() . "<br><BR>\r\n\r\n";

        }
    }


}

?>
