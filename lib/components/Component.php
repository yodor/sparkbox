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
    protected string $tagName = "div";

    /**
     * Property controlling the closing tag for this component
     * ex IMG does not need closing tag but SPAN does need
     * @var bool
     */
    protected bool $closingTagRequired = true;


    /**
     * Map to track HTML ID ownership across all component instances
     * @var array
     */
    private static array $IdMap = array();

    /**
     * Collection of HTML element attribute name/values
     * @var array
     */
    private array $attributes = array();

    /**
     * Collection of CSS style name/values
     * @var array
     */
    private array $style = array();


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

    private ?Container $caption_component = null;

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
            $this->setComponentClass(implode(" ", Spark::ClassChain($this)));
        }
        else {
            $this->setComponentClass(get_class($this));
        }

        SparkEventManager::emit(new ComponentEvent(ComponentEvent::COMPONENT_CREATED, $this));

    }

    protected function CreateCaption() : Container
    {
        $component = new Container(false);
        $component->setComponentClass("Caption");
        return $component;
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

    public function setName(string $name) : void
    {
        parent::setName($name);
        if ($name) {
            $this->setAttribute("name", $name);
        }
        else {
            $this->removeAttribute("name");
        }
    }

    /**
     * Set the inner contents (between the opening and closing tags) as HTML - written to the page as it is.
     *
     * For literals, tr() texts and HTML built or stored on purpose (descriptions, rich-text pages,
     * formatPrice() output, markup assembled from escaped parts). Anything that comes from visitors,
     * clients, imports or the database as plain text goes through setText() instead.
     * @param string $contents HTML
     * @return void
     * @see Component::setText()
     */
    public function setContents(string $contents) : void
    {
        $this->buffer->set($contents);
    }

    /**
     * Set the inner contents as text: HTML special characters are escaped (Spark::AttributeValue - existing
     * entities are kept, so imported text is not escaped twice). The text shows exactly as given and
     * cannot add markup to the page.
     *
     * For product, category and person names, addresses, notes, attribute and filter values, file names,
     * error messages - any value that is not HTML on purpose. To build markup from such values, escape each
     * part with Spark::AttributeValue() and pass the result to setContents().
     * @param string $text plain text
     * @return void
     * @see Component::setContents()
     */
    public function setText(string $text) : void
    {
        $this->setContents(Spark::AttributeValue($text));
    }

    /**
     * @return string The inner contents as they will be written - HTML (text set with setText() is escaped)
     */
    public function getContents(): string
    {
        return $this->buffer->get();
    }

    /**
     * Synchronizes internal object properties with HTML attributes.
     * * This handles how the component "appears" in the DOM tree.
     * Subclasses should overload this to map internal state (like URLs or sizes)
     * to the attributes array before rendering.
     */
    protected function syncAttrs(): void
    {
        // Implementation in subclasses
    }

    /**
     * Finalizes the component's state and structure before rendering.
     * * Reserved for complex transformations, such as manipulating child components,
     * conditional logic, or structural changes that go beyond simple attribute mapping.
     * * Called from startRender just before the opening tag is echoed.
     */
    protected function finalize(): void
    {
        $this->syncAttrs();
    }

    public function startRender(): void
    {
        if (!$this->render_enabled) return;

        $this->finalize();

        // Collect all markup parts using the consistent helper methods
        $markupParts = array_filter([
            $this->classToHtml(),
            $this->attrsToHtml(),
            $this->styleToHtml()
        ]);

        $markup = implode(" ", $markupParts);

        echo "<{$this->tagName}" . ($markup ? " $markup" : "") . ">";

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
    }

    protected function renderImpl(): void
    {
        if (!$this->render_enabled) return;

        if ($this->translation_enabled) {
            echo tr($this->buffer->get());
        }
        else {
            echo $this->buffer->get();
        }
    }

    public function finishRender(): void
    {
        if (!$this->render_enabled) return;

        if ($this->closingTagRequired) {
            echo "</$this->tagName>";
        }
    }

    /**
     * Render this component. Use cached version if found
     * @return void
     * @throws Exception
     */
    public function render(): void
    {
        if (!$this->render_enabled) return;

        $cacheEntry = CacheFactory::CacheEntryOutput($this);
        if ($cacheEntry === true) return;

        ob_start();
        $haveError = false;
        try {

            $this->startRender();
            $this->renderImpl();
            $this->finishRender();

        }
        catch (Exception $e) {
            //the error in place of the rest of the component - escaped, and always in the error log
            error_log("Render failed [" . get_class($this) . "]: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")");
            echo "<span class='RenderError'>" . htmlspecialchars($e->getMessage()) . "</span>";
            $haveError = true;
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        if (($cacheEntry instanceof CacheEntry) && !$haveError) {
            $cacheEntry->store($buffer, time());
        }

        echo $buffer;

    }

    /**
     * The page cache key of this component: the requested address, the language, the class and the name
     * (result views add their query). The address and not the script: under the modules every page is the
     * module index.php, and the rendered links (pager, letters) follow the address.
     * @return string
     */
    public function getCacheName() : string
    {
        return Component::CacheAddress()."-".get_class($this)."-".$this->getName();
    }

    /**
     * The requested path and query (without tracking parameters: utm_*, fbclid, gclid, msclkid), and the active
     * language - the part of the page cache key that identifies the page
     * @return string
     */
    public static function CacheAddress() : string
    {
        $parts = explode("?", (string)($_SERVER["REQUEST_URI"] ?? "/"), 2);
        $query = array();
        if (isset($parts[1])) parse_str($parts[1], $query);
        foreach (array_keys($query) as $name) {
            if (str_starts_with((string)$name, "utm_") || in_array($name, array("fbclid", "gclid", "msclkid"))) unset($query[$name]);
        }
        ksort($query);
        $language = class_exists("Translator", false) ? Translator::Instance()->activeCodeISO2() : "";
        return $parts[0] . "?" . http_build_query($query) . "|" . $language;
    }

    public function getCaption() : string
    {
        return $this->getCaptionComponent()->getContents();
    }

    /**
     * Set the caption contents as HTML (setContents() of the caption component) - for data use
     * getCaptionComponent()->setText()
     * @param string $caption
     * @return void
     */
    public function setCaption(string $caption) : void
    {
        $this->getCaptionComponent()->setContents($caption);
    }

    /**
     * Return Container used to render the caption space.
     * Lazy ctor for $this->caption_component
     * First call creates the caption component using CreateCaption() call
     *
     * @return Container
     */
    public function getCaptionComponent() : Container
    {
        if (is_null($this->caption_component)) {
            $this->caption_component = $this->CreateCaption();
        }
        return $this->caption_component;
    }

    public function setCaptionComponent(Container $component) : void
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
     * Set 'tooltip' attribute value - plain text, attribute values are escaped when rendered.
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
     * Set 'title' attribute value - plain text, attribute values are escaped when rendered.
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
        if ($cssClass) {
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
     * @param string $id
     * @return void
     * @throws Exception
     */
    public function setID(string $id): void
    {
        $this->setAttribute("id", $id);
    }

    /**
     * @return string
     */
    public function getID(): string
    {
        return $this->getAttribute("id");
    }

    /**
     * Set html attribute '$name' to '$value'. Values are plain text: they are escaped when rendered
     * (Spark::AttributeValue), so data can be passed as it is - do not escape it before.
     * @param string $name
     * @param string|null $value
     * @return void
     * @throws Exception
     */
    public function setAttribute(string $name, ?string $value="") : void
    {
        // Normalize attribute name to lowercase
        $name = strtolower($name);
        // HTML5 Attribute name validation:
        // No spaces, control characters, or symbols like / > =
        // We allow alphanumeric, dashes, underscores, and colons.
        if (preg_match('/[^\w\-:]/', $name)) {
            throw new Exception("Invalid HTML attribute name: '{$name}'. Use only alphanumeric, dashes, or colons.");
        }

        $value = trim((string)$value);

        if ($name === "id") {
            if (empty($value)) throw new Exception("HTML ID attribute cannot be empty.");

            // If ID is already in the map, it's blocked, regardless of who owns it
            if (isset(self::$IdMap[$value])) throw new Exception("HTML ID '{$value}' is already in use.");
            self::$IdMap[$value] = true;
        }

        $this->attributes[$name] = $value;

    }

    /**
     * Remove html attribute '$name'
     * @param string $name
     * @return void
     */
    public function removeAttribute(string $name) : void
    {
        // Normalize attribute name to lowercase
        $name = strtolower($name);

        if ($name === "id") {
            $id = $this->getAttribute("id");
            if ($id && isset(self::$IdMap[$id])) {
                unset(self::$IdMap[$id]);
            }
        }

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
        $name = strtolower($name);

        if (isset($this->attributes[$name])) return $this->attributes[$name];
        return "";
    }

    public function hasAttribute(string $name) : bool
    {
        $name = strtolower($name);

        return isset($this->attributes[$name]);
    }

    public function setRole(string $role) : void
    {
        $this->setAttribute("role", $role);
    }

    public function getRole() : string
    {
        return $this->getAttribute("role");
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

    /**
     * Transforms the internal attributes array into a space-separated HTML string.
     * Assumes all values are already processed (e.g., json_encoded) strings.
     * Returns empty string if no attributes are present.
     *
     * @return string
     */
    private function attrsToHtml(): string
    {
        if (empty($this->attributes)) return "";

        $htmlAttributes = array();
        foreach ($this->attributes as $name => $value) {

            //just in case cleanup
            $safeName = Spark::AttributeName($name);

            if (empty($safeName)) continue;

            if (is_null($value) || strlen($value) < 1) {
                $htmlAttributes[] = $safeName;
                continue;
            }

            $htmlAttributes[] = "{$safeName}='" . Spark::AttributeValue($value) . "'";
        }

        return implode(" ", $htmlAttributes);
    }

    /**
     * Formerly getStyleMarkup.
     * Transforms the style array into a valid HTML 'style' attribute.
     */
    private function styleToHtml(): string
    {
        $styles = [];
        foreach ($this->style as $name => $value) {
            if (empty($value)) continue;
            $styles[] = "$name:$value";
        }

        if (empty($styles)) return "";

        return "style='" . Spark::AttributeValue(implode("; ", $styles)) . "'";
    }

    /**
     * Builds the 'class' attribute string including component and custom classes.
     * @return string
     */
    private function classToHtml(): string
    {
        $css = [];

        $componentClass = trim($this->getComponentClass());
        if ($componentClass) $css[] = $componentClass;

        $extraClass = trim($this->getClassName());
        if ($extraClass) $css[] = $extraClass;

        if (empty($css)) return "";

        return "class='" . Spark::AttributeValue(implode(" ", $css)) . "'";
    }


}