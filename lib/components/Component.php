<?php
include_once("components/renderers/IRenderer.php");
include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("pages/HTMLPage.php");

class Component implements IRenderer, IHeadContents
{
    protected $tagName = "DIV";

    protected $index = -1;

    /**
     * @var string Additional CSS class name of this component
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
    protected $parent = NULL;

    protected $caption = "";

    protected $json_attributes = array();

    /**
     * @var string[] values of these attributes will be handled using htmlspecialchars.
     */
    protected $special_attributes = array("tooltip");

    protected $component_class = "";

    protected $contents = "";

    protected $name = "";

    public $translation_enabled = FALSE;
    public $render_tooltip = TRUE;
    public $render_enabled = TRUE;

    public function __construct()
    {
        //$this->component_class = get_class($this);

        $class_chain = class_parents($this);
        array_pop($class_chain);
        $class_chain = array_reverse($class_chain);
        $class_chain[] = get_class($this);

        $this->component_class = implode(" ", $class_chain);

        include_once("pages/SparkPage.php");
        $page = SparkPage::Instance();

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

    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setTagName(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function setContents(string $contents)
    {
        $this->contents = $contents;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    /**
     * Called in start render before prepareAttributes
     * Can be used from sub classess to set all required attributes
     */
    protected function processAttributes()
    {

    }

    public function startRender()
    {

        $this->processAttributes();
        $attrs = $this->prepareAttributes();
        echo "<$this->tagName $attrs>";

        $this->renderCaption();
    }

    public function renderCaption()
    {
        if (strlen($this->caption) > 0) {
            echo "<div class='caption'>";
            echo $this->caption;
            echo "</div>";
        }
    }

    protected function renderImpl()
    {
        if ($this->translation_enabled) {
            echo tr($this->contents);
        }
        else {
            echo $this->contents;
        }
    }

    public function finishRender()
    {

        echo "</$this->tagName>";
    }

    public function render()
    {
        if (!$this->render_enabled) return;

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
        $this->name = $name;
        $this->setAttribute("name", $name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setParent(Component $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Component|null
     */
    public function getParent(): ?Component
    {
        return $this->parent;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function setCaption(string $caption)
    {
        $this->caption = $caption;
    }

    public function getTooltipText(): string
    {
        return $this->getAttribute("tooltip");
    }

    public function setTooltipText(string $text)
    {
        $this->setAttribute("tooltip", $text);
    }

    public function getAttributes(): array
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

    public function setAttribute(string $name, string $value)
    {

        $this->attributes[$name] = $value;
    }

    public function clearAttribute(string $name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    public function getAttribute(string $name): string
    {
        if (isset($this->attributes[$name])) return $this->attributes[$name];
        return "";
    }

    public function setStyleAttribute(string $name, string $value)
    {
        $this->style[$name] = $value;
    }

    public function getStyleAttribute($name): string
    {
        if (isset($this->style[$name])) return $this->style[$name];
        return "";
    }

    public function getAttributesText(array $src_attributes = NULL): string
    {
        if (!$src_attributes) $src_attributes = $this->attributes;

        $attributes = array();
        foreach ($src_attributes as $name => $value) {

            if (!$this->render_tooltip && strcmp($name, "tooltip") == 0) continue;

            if (is_array($value)) {
                debug("component attribute value is array: " . get_class($this) . ": $name");
            }
            else if (is_null($value) || strlen($value) < 1) {

                $attributes[] = $name;

            }
            else {

                $attribute_value = attributeValue($value);

                if (in_array($name, $this->json_attributes)) {
                    $attributes[] = $name . "=" . json_string($attribute_value);
                }
                //                else if (in_array($name, $this->special_attributes)) {
                //                    $attributes[] = $name . "='" . htmlspecialchars(trim($attribute_value)) . "'";
                //                }
                else {
                    $attributes[] = $name . "='" . $attribute_value . "'";
                }
            }
        }

        return implode(" ", $attributes);

    }

    public function getStyleText(): string
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
            $cssClass .= trim($this->component_class);
        }
        if (strlen($this->className) > 0) {
            $cssClass .= " " . trim($this->className);
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
