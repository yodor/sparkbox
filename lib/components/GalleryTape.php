<?php
include_once("components/Component.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/renderers/items/DataIteratorItem.php");
include_once("components/renderers/IDataIteratorRenderer.php");

class GalleryTapeItem extends DataIteratorItem implements IPhotoRenderer
{

    /**
     * @var ImagePopup
     */
    protected ImagePopup $image_popup;

    public function __construct()
    {
        parent::__construct();

        $this->setComponentClass("slot");

        $this->image_popup = new ImagePopup();
        $this->image_popup->image()->setPhotoSize(-1, 128);

        $this->items()->append($this->image_popup);
    }

    public function setID(int $id) : void
    {
        parent::setID($id);
        $this->image_popup->setID($id);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->image_popup->setData($data);
    }

    public function setItemURL(URL $url) : void
    {
        $this->image_popup->setURL($url);
    }

    /**
     * Set the ImagePopup bean class
     * @param string $beanClass
     */
    public function setBeanClass(string $beanClass) : void
    {
        $this->image_popup->image()->getStorageItem()->className = $beanClass;
    }

    /**
     * Sets the component name and set the ImagePopup relation attribute
     * @param string $name
     */
    public function setName(string $name) : void
    {
        //parent::setName($name);

        $this->image_popup->setRelation($name);
    }

    public function setPhotoSize(int $width, int $height): void
    {
        $this->image_popup->image()->setPhotoSize($width, $height);
    }

    public function getPhotoWidth(): int
    {
        return $this->image_popup->image()->getPhotoWidth();
    }

    public function getPhotoHeight(): int
    {
        return $this->image_popup->image()->getPhotoHeight();
    }

}

class GalleryTape extends Component implements IDataIteratorRenderer
{

    /**
     * @var IDataIterator
     */
    protected $iterator;

    protected $item_renderer;

    protected $default_renderer;

    public function __construct()
    {
        parent::__construct();

    }

    public function getCacheName() : string
    {

        if (!($this->iterator instanceof SQLQuery)) return "";

        return parent::getCacheName()."-".$this->iterator->select->getSQL();

    }

    public function requiredStyle(): array
    {
        $ret = parent::requiredStyle();
        $ret[] = SPARK_LOCAL . "/css/GalleryTape.css";
        return $ret;
    }

    public function requiredScript(): array
    {
        $ret = parent::requiredScript();
        $ret[] = SPARK_LOCAL . "/js/SwipeListener.js";
        $ret[] = SPARK_LOCAL . "/js/GalleryTape.js";
        return $ret;
    }

    /**
     * Sets the component name and set the ImagePopup relation attribute
     * @param string $name
     */
    public function setName(string $name) : void
    {
        parent::setName($name);

        if (!$this->item_renderer) throw new Exception("Item renderer not set");
        $this->item_renderer->setName($name);
    }

    public function setIterator(IDataIterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    public function setItemRenderer(DataIteratorItem $item)
    {
        $this->item_renderer = $item;
    }

    public function getItemRenderer(): ?DataIteratorItem
    {
        return $this->item_renderer;
    }

    public function startRender()
    {
        parent::startRender();

        $this->iterator->exec();
    }

    protected function renderImpl()
    {

        echo "<div class='contents'>";

        echo "<div class='button left'></div>";

        echo "<div class='viewport'>";

        echo "<div class='slots'>";

        $pos = 0;

        while ($data = $this->iterator->next()) {

            $itemID = $data[$this->iterator->key()];

            $this->item_renderer->setAttribute("position", $pos);

            $this->item_renderer->setID($itemID);
            $this->item_renderer->setData($data);
            $this->item_renderer->render();

            $pos++;
        }
        echo "</div>"; //slots

        echo "</div>"; //viewport

        echo "<div class='button right' ></div>";

        echo "</div>";
    }

    public function render()
    {
        parent::render();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {

                let gallery_tape = new GalleryTape();
                gallery_tape.setName("<?php echo $this->getName();?>");
                gallery_tape.initialize();

            });

        </script>
        <?php
    }

}

?>
