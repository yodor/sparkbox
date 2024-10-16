<?php
include_once("objects/SparkObject.php");
include_once("components/renderers/IRenderer.php");
include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/renderers/ICacheable.php");
include_once("storage/CacheEntry.php");
include_once("utils/OutputBuffer.php");
include_once("objects/events/ComponentEvent.php");

/**
 * HTML Element Component
 */
class Component extends SparkObject implements IRenderer, IHeadContents, ICacheable
{

    /**
     * Buffer to hold the inner contents of this HTML element
     * @var OutputBuffer
     */
    protected OutputBuffer $buffer;

    /**
     * Property controlling the reachability for this component using PageCache
     * @var bool
     */
    protected bool $cacheable = false;

    /**
     * HTML tag name of this element ex DIV,SPAN,LABEL etc
     * @var string
     */
    protected string $tagName = "DIV";

    /**
     * Property controlling the closing tag for this component
     * ex IMG does not need closing tag but SPAN does need
     * @var bool
     */
    protected bool $closingTagRequired = true;


    /**
     * Collection of HTML element attribute name/values
     * @var array
     */
    protected array $attributes = array();

    /**
     * Collection of CSS style name/values
     * @var array
     */
    protected array $style = array();


    /**
     * Collection of attribute names that require their value as json encoded
     * @var array
     */
    protected array $json_attributes = array();


    /**
     * Property controlling automatic php class name to CSS class name mapping during constructor calls
     * If true the CSS class name is set using get_class of $this and its parents objects
     * If false CSS class name is equal to get_class($this) ie setComponentClass(get_class($this))
     * @var bool
     */
    protected bool $chained_component_class = true;

    /**
     * CSS component class
     * @var string
     */
    protected string $component_class = "";

    /**
     * Additional CSS class names
     * @var array
     */
    protected array $classNames = array();

    /**
     * Flag controlling the enablement of the render method of this component
     *
     * @var bool
     */
    protected bool $render_enabled = TRUE;


    protected string $caption = "";
    protected ?Component $caption_component = null;


    public bool $translation_enabled = FALSE;

    /**
     * Sets the component class to the value of get_class($this)
     * If '$chained_component_class' is true calls get_class on all parents and construct the component class from
     * their names including this component class
     * Ex: $cmp = new Container(true) will have component class = SparkObject Component Container
     * If '$chained_component_class' is false calls setComponentClass(get_class(this))
     * @param bool $chained_component_class enable inheritance component class
     */
    public function __construct(bool $chained_component_class = true)
    {
        parent::__construct();

        $this->buffer = new OutputBuffer();

        $this->chained_component_class = $chained_component_class;

        if ($this->chained_component_class) {
            $class_chain = class_parents($this);
            array_pop($class_chain);
            $class_chain = array_reverse($class_chain);
            $class_chain[] = get_class($this);
            $this->setComponentClass(implode(" ", $class_chain));
        }
        else {
            $this->setComponentClass(get_class($this));
        }


        SparkEventManager::emit(new ComponentEvent(ComponentEvent::COMPONENT_CREATED, $this));

    }

    /**
     * Property controlling the rendering of this component
     * @param bool $mode
     * @return void
     */
    public function setRenderEnabled(bool $mode) : void
    {
        $this->render_enabled = $mode;
    }

    public function isRenderEnabled() : bool
    {
        return $this->render_enabled;
    }

    public function buffer() : OutputBuffer
    {
        return $this->buffer;
    }

    public function setCacheable(bool $mode) : void
    {
        $this->cacheable = $mode;
    }

    public function isCacheable() : bool
    {
        return $this->cacheable;
    }

    /**
     * Set component CSS class name
     * @param string $name
     */
    public function setComponentClass(string $name) : void
    {
        $this->component_class = $name;
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

    /**
     * Set inner contents - between opening and closing tags
     * @param string $contents
     * @return void
     */
    public function setContents(string $contents) : void
    {
        $this->buffer->set($contents);
    }

    public function getContents(): string
    {
        return $this->buffer->get();
    }

    /**
     * Process all attributes of this component.
     * Called from startRender ie just before the tag is output.
     * Marshal php object property name/value to attribute name/value.
     * Ex property this->name is html attribute "name"
     * Default is to set the name attribute if not empty
     */
    protected function processAttributes(): void
    {
        if ($this->name) {
            $this->setAttribute("name", $this->name);
        }
    }

    public function startRender()
    {
        $this->processAttributes();
        $attrs = $this->prepareAttributes();
        echo "<";
        echo $this->tagName;
        if ($attrs) echo " ".$attrs;
        if (!$this->closingTagRequired) {
            echo "/";
        }
        echo ">";
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
            echo tr($this->buffer->get());
        }
        else {
            echo $this->buffer->get();
        }
    }

    public function finishRender()
    {
        if ($this->closingTagRequired) {
            echo "</$this->tagName>";
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

    public function getCaption() : string
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

    /**
     * Get 'tooltip' attribute value
     * @return string
     */
    public function getTooltip(): string
    {
        return $this->getAttribute("tooltip");
    }

    /**
     * Set 'tooltip' attribute value.
     * If empty $text remove the attribute if it was previously set
     * @param string $text
     * @return void
     */
    public function setTooltip(string $text) : void
    {
        if ($text) {
            $this->setAttribute("tooltip", $text);
        }
        else {
            $this->removeAttribute("tooltip");
        }
    }

    /**
     * Get 'title' attribute value
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getAttribute("title");
    }

    /**
     * Set 'title' attribute value
     * If empty $text remove the attribute if it was previously set
     * @param string $text
     * @return void
     */
    public function setTitle(string $text) : void
    {
        if ($text) {
            $this->setAttribute("title", $text);
        }
        else {
            $this->removeAttribute("title");
        }
    }

    /**
     * Get the html attribute name/value array
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getStyles() : array
    {
        return $this->style;
    }

    /**
     * Get all CSS class names as string
     * @return string
     */
    public function getClassName() : string
    {
        return implode(" ", array_keys($this->classNames));
    }

    /**
     * Set the CSS class name of this component, clearing any previously set class names
     * @param string $cssClass
     */
    public function setClassName(string $cssClass) : void
    {
        $this->classNames = array();
        if (!empty($cssClass)) {
            $this->classNames[$cssClass] = "";
        }
    }

    /**
     * Add CSS class name
     * @param string $cssClass
     */
    public function addClassName(string $cssClass) : void
    {
        if (!empty($cssClass)) {
            $this->classNames[$cssClass] = "";
        }
    }

    /**
     * Remove CSS class name
     * @param string $cssClass
     */
    public function removeClassName(string $cssClass) : void
    {
        if (array_key_exists($cssClass, $this->classNames)) {
            unset($this->classNames[$cssClass]);
        }
    }

    /**
     * Set html attribute '$name' to '$value'
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setAttribute(string $name, ?string $value="") : void
    {
        $this->attributes[$name] = trim((string)$value);
    }

    /**
     * Remove html attribute '$name'
     * @param string $name
     * @return void
     */
    public function removeAttribute(string $name) : void
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    /**
     * Get the value of attribute '$name'
     * If attribute is not set return empty string
     * @param string $name
     * @return string
     */
    public function getAttribute(string $name): string
    {
        if (isset($this->attributes[$name])) return $this->attributes[$name];
        return "";
    }

    public function hasAttribute(string $name) : bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Set inline style property '$name' to '$value'
     * Ex: $name="color" $value="red" will set style='color:red;'
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setStyle(string $name, string $value) : void
    {
        $this->style[$name] = $value;
    }

    /**
     * Get value of inline style property '$name'
     * If property is not set return empty string
     * @param string $name
     * @return string
     */
    public function getStyle(string $name): string
    {
        if (isset($this->style[$name])) return $this->style[$name];
        return "";
    }

    protected function getAttributesText(array $src_attributes = NULL): string
    {
        if (!$src_attributes) $src_attributes = $this->attributes;

        $attributes = array();
        foreach ($src_attributes as $name => $value) {

            //value can be "0" or ""
            if (is_null($value) || strlen($value) < 1) {
                $attributes[] = $name;
                continue;
            }

            if (is_array($value) || in_array($name, $this->json_attributes)) {
                //debug("component attribute value is array: " . get_class($this) . ": $name");
                $value = json_encode($value);
            }

            $attributes[] = $name . "='" . attributeValue($value) . "'";

        }

        return trim(implode(" ", $attributes));

    }

    protected function getStyleText(): string
    {

        $result = "";

        $styles = array();
        foreach ($this->style as $style_name => $value) {
            if (empty($value)) continue;
            $styles[] = $style_name . ":" . $value;
        }

        if (count($styles) > 0) {
            $result = "style='".attributeValue(implode("; ", $styles))."'";
        }
        return $result;

    }

    protected function prepareAttributes() : string
    {
        $css = array();

        //component class
        $componentClass = trim($this->getComponentClass());
        if (!empty($componentClass)) {
            $css[] = $componentClass;
        }

        //this class names
        $className = trim($this->getClassName());
        if (!empty($className)) {
            $css[] = $className;
        }

        $attrs = array();
        if (count($css)>0) {
            $attrs[] = "class='" . attributeValue(implode(" ", $css)) . "'";
        }
        $attrs[] = $this->getAttributesText();
        $attrs[] = $this->getStyleText();
        return trim(implode(" ", $attrs));

    }

    public function appendAttributes(array $attributes) : void
    {
        foreach ($attributes as $name => $value) {
            $this->attributes[$name] = $value;
        }
    }

}

?>
