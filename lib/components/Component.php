<?php
include_once("objects/SparkObservable.php");
include_once("components/renderers/IRenderer.php");
include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/renderers/ICacheable.php");
include_once("storage/CacheEntry.php");
include_once("utils/OutputBuffer.php");

class Component extends SparkObservable implements IRenderer, IHeadContents, ICacheable
{

    protected OutputBuffer $buffer;

    protected bool $cacheable = false;

    /**
     * HTML tag of this component
     * @var string
     */
    protected string $tagName = "DIV";

    protected bool $closingTagRequired = true;

    protected int $index = -1;



    /**
     * Collection of HTML attribute name/values
     * @var array
     */
    protected array $attributes = array();

    /**
     * Collection oh CSS style name/values
     * @var array
     */
    protected array $style = array();


    /**
     * Collection of atrribue names that require json encoded value
     * @var array
     */
    protected array $json_attributes = array();


    /**
     * If true the component class is set to this object class name + all parent object class names
     * If false only this object class name is set as $component_class
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

    public bool $translation_enabled = FALSE;
    public bool $render_tooltip = TRUE;
    public bool $render_enabled = TRUE;

    protected string $caption = "";
    protected ?Component $caption_component = null;


    /**
     * Component constructor.
     * Creates default component class by using the inheritance chain get_class
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


        include_once("pages/SparkPage.php");
        $page = SparkPage::Instance();

        if ($page instanceof SparkPage) {
            $page->addComponent($this);
        }

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
     * Set component css class name
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
        $this->buffer->set($contents);
    }

    public function getContents(): string
    {
        return $this->buffer->get();
    }

    /**
     * Called in start render before prepareAttributes
     * Can be used from sub classess to set all required attributes
     */
    protected function processAttributes(): void
    {

    }

    public function startRender()
    {
        $this->processAttributes();
        $attrs = $this->prepareAttributes();
        echo "<";
        echo $this->tagName;
        if (!empty($attrs)) echo " ".$attrs;
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

    public function setTooltipText(string $text) : void
    {
        if (!empty($text)) {
            $this->setAttribute("tooltip", $text);
        }
        else {
            $this->removeAttribute("tooltip");
        }
    }

    /**
     * Get title attrobite
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getAttribute("title");
    }

    /**
     * Set title attribute
     * @param string $text
     * @return void
     */
    public function setTitle(string $text) : void
    {
        $this->setAttribute("title", $text);
    }


    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getStyles() : array
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

    public function setAttribute(string $name, string $value) : void
    {
        $this->attributes[$name] = trim($value);
    }

    public function removeAttribute(string $name) : void
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

    public function setStyleAttribute(string $name, string $value) : void
    {
        $this->style[$name] = $value;
    }

    public function getStyleAttribute(string $name): string
    {
        if (isset($this->style[$name])) return $this->style[$name];
        return "";
    }

    protected function getAttributesText(array $src_attributes = NULL): string
    {
        if (!$src_attributes) $src_attributes = $this->attributes;

        $attributes = array();
        foreach ($src_attributes as $name => $value) {

            if (!$this->render_tooltip && strcmp($name, "tooltip") == 0) continue;

            //value can be "0"
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

        return implode(" ", $attributes);

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

        return implode(" ", $attrs);
    }

    public function appendAttributes(array $attributes) : void
    {
        foreach ($attributes as $name => $value) {
            $this->attributes[$name] = $value;
        }
    }

}

?>
