<?php
include_once("components/Container.php");
include_once("components/Component.php");
include_once("components/ImagePopup.php");
include_once("components/Image.php");
include_once("components/InlineScript.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("storage/StorageItem.php");
include_once("utils/ImageSizes.php");
include_once("utils/url/URL.php");
include_once("iterators/IDataIterator.php");

/**
 * How the slider changes its photo
 */
enum SliderEffect : string
{
    //the row of photos moves
    case SLIDE = "slide";
    //the photos fade into each other (one photo in view)
    case FADE = "fade";
}

/**
 * The dots under the photos - none, plain dots or small photos (rendered at ImageSlider::setDotSize())
 */
enum SliderDots : string
{
    case NONE = "none";
    case SIMPLE = "simple";
    case IMAGE = "image";
}

/**
 * Photos in a row moved by the arrows, the dots, a swipe or the autoplay (sparkfront/js/ImageSlider.js). A photo
 * opens the ImagePopup with the photos of its slider, or follows its link. After the last photo it comes back to the
 * first.
 *
 *   .ImageSlider[name]            (+ .fade, .strip)
 *     .viewport
 *       a.ImagePopup.item > img   one per photo
 *     .nav-arrow.prev / .next     more than one photo
 *     .dots > .dot [> img]        SliderDots
 *
 * The look is css (sparkfront/css/ImageSlider.css): --slider-per-view (photos in view, default 1), --slider-gap;
 * setStrip(): the photos in their own widths at --slider-photo-height (a tape of photos). The size the storage
 * sends is setPhotoSize() (ImageSizes), the box and the fit (object-fit) are the css of the page.
 */
class ImageSlider extends Container implements IPhotoRenderer
{
    protected Container $viewport;
    protected Container $dots;
    protected Component $prev;
    protected Component $next;

    /**
     * @var array<Image> the photos, in their order
     */
    protected array $images = array();

    protected int $width = 0;
    protected int $height = 0;

    protected SliderEffect $effect = SliderEffect::SLIDE;
    protected SliderDots $dotStyle = SliderDots::SIMPLE;
    protected int $dotSize = 0;

    protected bool $autoplay = false;
    protected int $autoplayDelay = 4000;

    protected bool $priority = false;

    protected bool $arrows = true;

    private static int $count = 0;

    public function __construct()
    {
        parent::__construct();

        //the buttons of the popup of its photos (ImagePopup.js)
        HTMLHead::Instance()->addTexts(...ImagePopup::SCRIPT_TEXTS);

        $this->dotSize = ImageSize::Small->value;

        $this->viewport = new Container(false);
        $this->viewport->setComponentClass("viewport");
        $this->viewport->setAttribute("draggable", "false");
        $this->items()->append($this->viewport);

        $this->prev = new Component(false);
        $this->prev->setComponentClass("nav-arrow prev");
        $this->prev->setContents("&#10094;");
        $this->items()->append($this->prev);

        $this->next = new Component(false);
        $this->next->setComponentClass("nav-arrow next");
        $this->next->setContents("&#10095;");
        $this->items()->append($this->next);

        $this->dots = new Container(false);
        $this->dots->setComponentClass("dots");
        $this->items()->append($this->dots);
    }

    public function requiredStyle(): array
    {
        $ret = parent::requiredStyle();
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/css/ImagePopup.css";
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/css/ImageSlider.css";
        return $ret;
    }

    public function requiredScript(): array
    {
        $ret = parent::requiredScript();
        //the photos can be added while rendering (after the head) - the popup script is listed here
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/ImagePopup.js";
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/SwipeListener.js";
        $ret[] = Spark::Get(Config::SPARK_LOCAL) . "/js/ImageSlider.js";
        return $ret;
    }

    /**
     * Add a photo
     * @param StorageItem $item the photo (a copy is kept)
     * @param URL|null $link a link instead of the popup
     * @param string $title the title and alt text
     * @return ImagePopup the item - for more attributes
     */
    public function addImage(StorageItem $item, ?URL $link = null, string $title = "") : ImagePopup
    {
        $popup = new ImagePopup(clone $item);
        $popup->addClassName("item");
        $popup->setAttribute("draggable", "false");
        if ($link) $popup->setURL($link);
        if ($title !== "") $popup->setTitle($title);

        $image = $popup->image();
        $image->setAttribute("draggable", "false");
        if ($title !== "") $image->setAttribute("alt", $title);

        $this->viewport->items()->append($popup);
        $this->images[] = $image;
        return $popup;
    }

    /**
     * Add a photo for each row of '$iterator' - the photo id is the key of the iterator
     * @param IDataIterator $iterator executed here
     * @param string $beanClass the bean of the photos
     * @param URL|null $link a link instead of the popup - its DataParameters take the values of the row
     * @param string $titleKey the column of the title, optional
     */
    public function addRows(IDataIterator $iterator, string $beanClass, ?URL $link = null, string $titleKey = "") : void
    {
        $iterator->exec();
        while ($row = $iterator->next()) {
            $rowLink = null;
            if ($link) {
                $rowLink = clone $link;
                $rowLink->setData($row);
            }
            $title = ($titleKey !== "") ? (string)($row[$titleKey] ?? "") : "";
            $this->addImage(new StorageItem((int)$row[$iterator->key()], $beanClass), $rowLink, $title);
        }
    }

    /**
     * Remove every photo (a renderer that shows other photos for each row)
     */
    public function clear() : void
    {
        $this->viewport->items()->clear();
        $this->images = array();
    }

    public function count() : int
    {
        return count($this->images);
    }

    public function viewport() : Container
    {
        return $this->viewport;
    }

    public function setEffect(SliderEffect $effect) : void
    {
        $this->effect = $effect;
    }

    public function setDots(SliderDots $style) : void
    {
        $this->dotStyle = $style;
    }

    /**
     * Show the arrows (more than one photo) - default on
     */
    public function setArrows(bool $mode) : void
    {
        $this->arrows = $mode;
    }

    /**
     * The size of the photo dots (SliderDots::IMAGE) - default ImageSize::Small
     */
    public function setDotSize(int|IImageSize $size) : void
    {
        $this->dotSize = ImageSizes::Pixels($size);
    }

    /**
     * Move to the next photo every '$delay' ms (stops while the pointer is over the slider)
     */
    public function setAutoplay(bool $mode, int $delay = 4000) : void
    {
        $this->autoplay = $mode;
        $this->autoplayDelay = $delay;
    }

    /**
     * The photos in their own widths at --slider-photo-height (a tape of photos)
     */
    public function setStrip(bool $mode) : void
    {
        if ($mode) {
            $this->addClassName("strip");
        }
        else {
            $this->removeClassName("strip");
        }
    }

    /**
     * The first photo is the main photo of the page (fetchpriority high) - else it loads as the page needs it
     */
    public function setPriority(bool $mode) : void
    {
        $this->priority = $mode;
    }

    public function setPhotoSize(int|IImageSize $width, int|IImageSize $height = 0) : void
    {
        $this->width = ImageSizes::Pixels($width);
        $this->height = ImageSizes::Pixels($height);
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }

    /**
     * The name the script finds the slider by - one is given when none is set
     */
    protected function sliderName() : string
    {
        if ($this->getName() === "") {
            ImageSlider::$count++;
            $this->setName("ImageSlider" . ImageSlider::$count);
        }
        return $this->getName();
    }

    public function startRender(): void
    {
        $name = $this->sliderName();

        $this->removeClassName("fade");
        if ($this->effect === SliderEffect::FADE) $this->addClassName("fade");

        $this->dots->items()->clear();
        $many = (count($this->images) > 1);

        foreach ($this->images as $pos => $image) {
            $image->setPhotoSize($this->width, $this->height);
            $image->removeAttribute("loading");
            $image->removeAttribute("fetchpriority");
            if ($pos > 0) {
                $image->setAttribute("loading", "lazy");
            }
            else if ($this->priority) {
                $image->setAttribute("fetchpriority", "high");
            }

            if (!$many || $this->dotStyle === SliderDots::NONE) continue;

            $dot = new Container(false);
            $dot->setTagName("span");
            $dot->setComponentClass("dot");
            if ($this->dotStyle === SliderDots::IMAGE) {
                $dot->addClassName("image");
                $thumb = new Image();
                $thumb->setStorageItem(clone $image->getStorageItem());
                $thumb->setPhotoSize($this->dotSize, $this->dotSize);
                $thumb->setAttribute("alt", (string)$image->getAttribute("alt"));
                $thumb->setAttribute("draggable", "false");
                $dot->items()->append($thumb);
            }
            $this->dots->items()->append($dot);
        }

        //the popup shows the photos of this slider
        $iterator = $this->viewport->items()->iterator();
        while ($item = $iterator->next()) {
            if ($item instanceof ImagePopup) $item->setRelation($name);
        }

        $this->prev->setRenderEnabled($many && $this->arrows);
        $this->next->setRenderEnabled($many && $this->arrows);
        $this->dots->setRenderEnabled($many && $this->dotStyle !== SliderDots::NONE);

        parent::startRender();
    }

    /**
     * The script of the slider after it
     */
    public function finishRender(): void
    {
        parent::finishRender();

        if (count($this->images) < 2) return;

        $name = json_encode($this->getName());
        $options = json_encode(array(
            "effect" => $this->effect->value,
            "autoplay" => $this->autoplay,
            "delay" => $this->autoplayDelay,
        ));
        $script = new InlineScript(true);
        $script->setCode("ImageSlider.Start($name, $options);");
        $script->render();
    }
}
