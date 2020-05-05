<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/input/renderers/DataSourceField.php");
include_once("lib/input/renderers/DataSourceItem.php");

class FreetextWrapper extends Component
{
    public function __construct()
    {
        parent::__construct();
    }

    public function renderImpl()
    {

    }
}

class SelectOption extends DataSourceItem
{
    public function __construct()
    {
        parent::__construct();

    }

    public function startRender()
    {
        $attribs = $this->prepareAttributes();

        echo "<option value='{$this->value}' $attribs ";
        if ($this->isSelected()) echo "SELECTED";
        echo ">";
    }

    public function finishRender()
    {
        echo "</option>";
    }

    public function renderImpl()
    {

        echo $this->label;
    }

}

class SelectField extends DataSourceField
{

    public $na_str = "--- SELECT ---";
    public $na_val = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->setItemRenderer(new SelectOption());
    }


    public function renderField(DataInput $field, $render_index = -1)
    {

        if ($field->getLinkField() instanceof DataInput) {
            $this->setFieldAttribute("onChange", "javascript:toggleLinkedField(this)");
            if ($this->freetext_value) {
                $this->setFieldAttribute("link_value", $this->freetext_value);
            }

        }

        parent::renderField($field, $render_index);

    }

    public function finishRender()
    {


        $field_value = $this->field->getValue();
        $field_name = $this->field->getName();


        if ($this->freetext_value) {
            $lf = $this->field->getLinkField();

            $cmp = new FreetextWrapper();
            $cmp->setAttribute("field", $field_name);
            if (strcmp($field_value, $this->freetext_value) === 0) {
            }
            else {
                $cmp->setClassName($cmp->getClassName() . " hidden");
            }
            $cmp->startRender();
            $lf->getLabelRenderer()->renderLabel($lf);
            $lf->getRenderer()->renderField($lf);
            $cmp->finishRender();

        }

        parent::finishRender();

    }

    protected function startRenderItems()
    {
        //prepare the default select value
        parent::startRenderItems();

        $attrs = $this->prepareFieldAttributes();
        echo "<select $attrs >";

        if ($this->na_str) {
            $item = clone $this->item;
            $item->setID(-1);
            $item->setValue($this->na_val);
            $item->setLabel($this->na_str);
            $item->setIndex(-1);

            $selected = $this->isModelSelected($this->na_val, $this->field->getValue());

            $item->setSelected($selected);

            $item->render();

        }
    }

    protected function finishRenderItems()
    {

        echo "</select>";
        parent::finishRenderItems();
    }

    protected function isModelSelected($value, $field_values)
    {
        $selected = false;
        if (is_array($field_values)) {
            foreach ($field_values as $idx => $field_value) {
                if (strcmp($value, $field_value) == 0) {
                    $selected = true;
                    break;
                }
            }
        }
        else {
            if (strcmp($value, $field_values) == 0) {
                $selected = true;
            }
        }
        return $selected;
    }

    public function createFreetextField(DataInput $field)
    {
        $field_link = new DataInput($field->getName() . "_other", "Please Specify", 0);
        $field_link->setRenderer(new TextField());
        $field_link->setLinkMode(true);

        $field->setLinkField($field_link);
        $field_link->setLinkField($field);


        return $field_link;
    }
}

class SelectMultipleField extends SelectField
{
    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("multiple", "");
        $this->addClassName("SelectField");
        $this->na_str = "";
    }

    protected function startRenderItems()
    {
        $this->setFieldAttribute("name", $this->field->getName() . "[]");
        parent::startRenderItems();
    }


}

?>
