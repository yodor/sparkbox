<?php
include_once("objects/SparkObservable.php");
include_once("components/renderers/IRenderer.php");
include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/renderers/ICacheable.php");
include_once("storage/CacheEntry.php");

class Component extends SparkObservable implements IRenderer, IHeadContents, ICacheable
{

    protected bool $cacheable = false;

    /**
     * HTML tag of this component
     * @var string
     */
    protected string $tagName = "DIV";

    protected bool $closingTagRequired = true;

    protected int $index = -1;

    /**
     * @var array Collection of CSS classes for this component, in addition to the automatic '$component_class'
     */
    protected array $classNames = array();

    /**
     * @var array Collection of HTML attribute name/values
     */
    protected array $attributes = array();

    protected array $style = array();

    protected string $caption = "";

    protected array $json_attributes = array();

    /**
     * @var string[] values of these attributes will be handled using htmlspecialchars.
     */
    protected array $special_attributes = array("tooltip");

    /**
     * @var string Automatic css class string. Build from all the class hierarchy. Override with setComponentClass
     */
    protected string $component_class = "";

    protected string $contents = "";

    public bool $translation_enabled = FALSE;
    public bool $render_tooltip = TRUE;
    public bool $render_enabled = TRUE;

    protected ?Component $caption_component = null;

    /**
     * Component constructor.
     * Creates default component class by using the inheritance chain get_class
     */
    public function __construct()
    {

        parent::__construct();

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

    public function setCacheable(bool $mode) : void
    {
        $this->cacheable = true;
    }
    public function isCacheable() : bool
    {
        return $this->cacheable;
    }
    /**
     * Override the automatic class name constructed from the inheritance chain
     * @param string $componentClass
     */
    public function setComponentClass(string $componentClass) : void
    {
        $this->component_class = $componentClass;
    }

    public function getComponentClass(): string
    {
        return $this->component_class;
    }

    public function requiredStyle() : array
    {
        return array();
    }

    public function requiredScript() : array
    {
        return array();
    }

    public function requiredMeta() : array
    {
        return array();
    }

    public function setIndex(int $index) : void
    {
        $this->index = $index;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setTagName(string $tagName) : void
    {
        $this->tagName = $tagName;
    }

    public function setClosingTagRequired(bool $mode) : void
    {
        $this->closingTagRequired = $mode;
    }

    public function isClosingTagRequired() : bool
    {
        return $this->closingTagRequired;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function setContents(string $contents) : void
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
        echo "<$this->tagName $attrs>\n";

        $this->renderCaption();
    }

    /**
     *
     * @return void
     * @throws Exception
     */
    protected function renderCaption() : void
    {
        if ($this->caption_component instanceof Component) {
            $this->caption_component->render();
        }
        else if ($this->caption) {
            echo "<div class='Caption'>$this->caption</div>";
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
        if ($this->closingTagRequired) {
            echo "</$this->tagName>\n";
        }
    }

    /**
     * Render this component. Use cached version if found
     * @return void
     * @throws Exception
     */
    public function render()
    {
        if (!$this->render_enabled) return;

        $cacheEntry = null;

        if ($this->cacheable && PAGE_CACHE_ENABLED) {

            $cacheName = $this->getCacheName();
            if (!empty($cacheName)) {
                debug("Cacheable component: ".get_class($this)." | Cache name: ".$cacheName);

                $cacheEntry = CacheEntry::PageCacheEntry(get_class($this) . "-" . sparkHash($cacheName));
                if ($cacheEntry->getFile()->exists()) {

                    $entryStamp = $cacheEntry->lastModified();
                    $timeStamp = time();
                    $entryAge = ($timeStamp - $entryStamp);
                    $remainingTTL = PAGE_CACHE_TTL - $entryAge;

                    debug("CacheEntry exists - lastModified: " . $entryStamp . " | Remaining TTL: " . $remainingTTL);

                    if ($remainingTTL > 0) {
                        //output cached data
                        $cacheEntry->output();
                        return;
                    }
                }
            }
        }

        ob_start();
        $haveError = false;
        try {

            $this->startRender();
            $this->renderImpl();
            $this->finishRender();

        }
        catch (Exception $e) {
            echo $e->getMessage();
            $haveError = true;
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        if (($cacheEntry instanceof CacheEntry) && !$haveError) {
            $cacheEntry->store($buffer, time());
        }

        echo $buffer;

    }

    public function getCacheName() : string
    {
        return basename($_SERVER["SCRIPT_FILENAME"])."-".get_class($this)."-".$this->getName();
    }

    public function setName(string $name) : void
    {
        parent::setName($name);
        $this->setAttribute("name", $name);
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function setCaption(string $caption) : void
    {
        $this->caption = $caption;
        if ($this->caption_component instanceof Component) {
            $this->caption_component->setContents($caption);
        }
    }

    public function getCaptionComponent() : ?Component
    {
        return $this->caption_component;
    }

    public function setCaptionComponent(Component $component) : void
    {
        $this->caption_component = $component;
    }

    public function getTooltipText(): string
    {
        return $this->getAttribute("tooltip");
    }

    public function setTooltipText(string $text)
    {
        if (!empty($text)) {
            $this->setAttribute("tooltip", $text);
        }
        else {
            $this->clearAttribute("tooltip");
        }
    }

    public function getTitle(): string
    {
        return $this->getAttribute("title");
    }

    public function setTitle(string $text) : void
    {
        $this->setAttribute("title", $text);
    }


    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getStyles()
    {
        return $this->style;
    }

    public function getClassName() : string
    {
        return implode(" ", array_keys($this->classNames));
    }

    /**
     * Set the CSS class of this component, clearing any previously set class names
     * @param string $cssClass
     */
    public function setClassName(string $cssClass)
    {
        $this->classNames = array();
        if (!empty($cssClass)) {
            $this->classNames[$cssClass] = "";
        }
    }

    /**
     * Add CSS class name to this components class names
     * @param string $cssClass
     */
    public function addClassName(string $cssClass)
    {
        if (!empty($cssClass)) {
            $this->classNames[$cssClass] = "";
        }
    }

    /**
     * Remove CSS class specified in '$cssClass'
     * @param string $cssClass
     */
    public function removeClassName(string $cssClass)
    {
        if (array_key_exists($cssClass, $this->classNames)) {
            unset($this->classNames[$cssClass]);
        }
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

    /**
     * Get value of attribute '$name'
     * If attribute is not set return empty string
     * @param string $name Name of attribte
     * @return string Value of attribute
     */
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

        $cssClass = array();

        if (strlen($this->component_class) > 0) {
            $cssClass[] = trim($this->component_class);
        }

        $className = $this->getClassName();
        if (strlen($className) > 0) {
            $cssClass[] = $this->getClassName();
        }

        if (count($cssClass)>0) {
            $attrs .= " class='" . implode(" ", $cssClass) . "' ";
        }

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
