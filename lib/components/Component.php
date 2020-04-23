<?php
include_once("lib/components/renderers/IRenderer.php");
include_once("lib/components/renderers/IHeadContents.php");
include_once("lib/components/renderers/IPageComponent.php");
include_once("lib/pages/HTMLPage.php");

abstract class Component implements IRenderer, IHeadContents
{
    /**
     * @var string CSS class name of this component
     */
    protected $className = "";

    /**
     * @var array Collection of HTML attribute name/values
     */
    protected $attributes = array();

    protected $style = array();

    /**
     * @var Component
     */
    protected $parent_component = NULL;

    protected $caption = "";

    protected $json_attributes = array();

    /**
     * @var string[] values of these attributes will be handled using htmlspecialchars.
     */
    protected $special_attributes = array("tooltip");

    protected $component_class = "";

    public $render_tooltip = true;

    public function __construct()
    {
        //$this->component_class = get_class($this);

        $class_chain = class_parents($this);
        array_pop($class_chain);
        $class_chain = array_reverse($class_chain);
        $class_chain[] = get_class($this);


        $this->component_class = implode(" ", $class_chain);

        $page = HTMLPage::Instance();

        if ($page instanceof SparkPage) {
            $page->addComponent($this);
        }

    }

    public function requiredStyle()
    {
        return array();
    }

    public function requiredScript()
    {
        return array();
    }

    public function requiredMeta()
    {
        return array();
    }

    protected abstract function renderImpl();

    public function startRender()
    {
        $attrs = $this->prepareAttributes();
        echo "<div $attrs>";
    }

    public function finishRender()
    {
        echo "</div>";
    }

    public function render()
    {
        try {
            $this->startRender();
            $this->renderImpl();
            $this->finishRender();
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function setName(string $name)
    {
        $this->setAttribute("name", $name);
    }

    public function getName()
    {
        return $this->getAttribute("name");
    }

    public function setParent(Component $parent)
    {
        $this->parent_component = $parent;
    }

    /**
     * @return Component|null
     */
    public function getParent()
    {
        return $this->parent_component;
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function setCaption(string $caption)
    {
        $this->caption = $caption;
    }

    public function getTooltipText()
    {
        return $this->getAttribute("tooltip");
    }

    public function setTooltipText(string $text)
    {
        $this->setAttribute("tooltip", $text);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getStyles()
    {
        return $this->style;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function setClassName(string $className)
    {
        $this->className = $className;
    }

    public function addClassName(string $className)
    {
        $this->className .= " " . $className;
    }

    public function setAttribute(string $name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function clearAttribute(string $name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name];
    }

    public function setStyleAttribute(string $name, $value)
    {
        $this->style[$name] = $value;
        return $this;
    }

    public function getStyleAttribute($name)
    {
        return $this->style[$name];
    }

    public function getAttributesText(array $src_attributes = NULL)
    {
        if (!$src_attributes) $src_attributes = $this->attributes;

        $attributes = array();
        foreach ($src_attributes as $name => $value) {

            if (!$this->render_tooltip && strcmp($name, "tooltip") == 0) continue;


            if (is_null($value) || strlen($value) < 1) {

                $attributes[] = $name;

            }
            else {

                $attribute_value = attributeValue($value);

                if (in_array($name, $this->json_attributes)) {
                    $attributes[] = $name . "=" . json_string($attribute_value);
                }
                else if (in_array($name, $this->special_attributes)) {
                    $attributes[] = $name . "='" . htmlspecialchars(trim($attribute_value)) . "'";
                }
                else {
                    $attributes[] = $name . "='" . $attribute_value . "'";
                }
            }
        }

        return implode(" ", $attributes);

    }

    public function getStyleText()
    {

        $styles = array();

        foreach ($this->style as $style_name => $value) {
            if (strlen($value) < 1) continue;

            $styles[] = $style_name . ":" . $value;
        }

        if (count($styles) > 0) {
            $style_text = implode(";", $styles);

            return " style='$style_text' ";
        }
        else {
            return "";
        }

    }

    protected function prepareAttributes()
    {
        $attrs = "";
        //$class_names = trim($this->component_class . " " . $this->className);
        $cssClass = "";
        if (strlen($this->component_class) > 0) {
            $cssClass.=trim($this->component_class);
        }
        if (strlen($this->className)>0) {
            $cssClass.=" ".trim($this->className);
        }
        $attrs .= " class='$cssClass' ";

        $attrs .= $this->getAttributesText();
        $attrs .= $this->getStyleText();

        return $attrs;
    }

    public function appendAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->attributes[$name] = $value;
        }
    }

}

?>
