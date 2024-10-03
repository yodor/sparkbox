<?php
include_once("input/renderers/InputFieldTag.php");
include_once("components/DataList.php");

class SliderField extends InputFieldTag
{

    protected DataList $dataList;

    protected LabelSpan $label;
    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->input->setType("range");

        $this->dataList = new DataList();

        $this->items()->append($this->dataList);

        $this->label = new LabelSpan("Value: ");
        $this->items->append($this->label);

    }

    public function label() : LabelSpan
    {
        return $this->label;
    }

    public function list() : DataList
    {
        return $this->dataList;
    }

    protected function processAttributes() : void
    {

        parent::processAttributes();

        $dataValue = $this->dataInput->getValue();
        $value = (int)$dataValue;

        $max = intval($this->getMaximum());
        $min = intval($this->getMinimum());

        if ($min>0) {
            if ($value < $min) {
                $dataValue = $min;
            }
        }
        if ($max>0) {
            if ($value > $max) {
                $dataValue = $max;
            }
        }

        $this->input->setAttribute("value", $dataValue);

        if ($this->dataList->items()->count()<1) {
            $this->dataList->setRenderEnabled(false);
        }
        else {
            $this->dataList->setID($this->dataInput->getName());
            $this->setAttribute("list", $this->dataInput->getName());
        }
        $this->label->span()->setContents((string)$dataValue);

    }

    public function setMinimum(string $minimum) : void
    {
        $this->input->setAttribute("min", $minimum);
    }
    public function getMinimum() : string
    {
        return $this->input->getAttribute("min");
    }

    public function setMaximum(string $maximum) : void
    {
        $this->input->setAttribute("max", $maximum);
    }
    public function getMaximum() : string
    {
        return $this->input->getAttribute("max");
    }

    public function setStep(string $step) : void
    {
        $this->input->setAttribute("step", $step);
    }
    public function getStep() : string
    {
        return $this->input->getAttribute("step");
    }

    public function addMarker(string $value, string $label) : void
    {
        $this->dataList->items()->append(new DataListItem($value,$label));
    }

    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type="text/javascript">
            onPageLoad(function(){
                const name = "<?php echo $this->dataInput->getName();?>";

                const input = document.querySelector("input[type='range'][name='"+name+"']");
                const sliderField = input.closest(".SliderField");
                const value = sliderField.querySelector("SPAN");

                value.textContent = input.value;

                input.addEventListener("input", (event) => {
                    value.textContent = event.target.value;
                });
            })
        </script>
        <?php

    }

}

?>