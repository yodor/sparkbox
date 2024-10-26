<?php
include_once("components/renderers/cells/TableCell.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/TableColumn.php");
include_once("storage/StorageItem.php");

class ImageCell extends TableCell implements IPhotoRenderer
{

    /**
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

    protected int $list_limit = 0;

    protected string $blob_field = "";

    protected string $relateField = "";

    protected array $elements = array();

    protected ImagePopup $image_popup;

    protected static $DefaultWidth = 128;
    protected static $DefaultHeight = -1;

    protected bool $sortable = FALSE;

    protected string $error = "";

    protected ClosureComponent $imageList;

    public static function SetDefaultPhotoSize(int $width, int $height): void
    {
        ImageCell::$DefaultWidth = $width;
        ImageCell::$DefaultHeight = $height;
    }

    public function __construct(int $width = -1, int $height = -1)
    {
        parent::__construct();

        $this->list_limit = 1;

        if ($width < 1 && $height < 1) {
            $width = ImageCell::$DefaultWidth;
            $height = ImageCell::$DefaultHeight;
        }

        $this->image_popup = new ImagePopup();
        $this->image_popup->image()->setPhotoSize($width, $height);
        $this->image_popup->setAttribute("relation", "ImageCell");

        $this->imageList = new ClosureComponent($this->renderImageItems(...));
        $this->imageList->setComponentClass("ImageList");

        $this->items()->append($this->imageList);
    }


    public function setBean(DBTableBean $bean, string $relateField = ""): void
    {
        $this->bean = $bean;
        if ($relateField) {
            $this->relateField = $relateField;
        }

    }

    public function setLimit(int $num): void
    {
        $this->list_limit = $num;
    }

    public function setBlobField(string $blob_field): void
    {
        $this->blob_field = $blob_field;
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

    public function setAction(Action $a): void
    {
        $this->action = $a;
    }

    protected function getDataValues(?string $values): array
    {
        if (is_null($values)) return array();
        $values = explode("|", $values);
        if ($this->list_limit > 0) {
            array_splice($values, $this->list_limit);
        }
        return $values;
    }

    protected function constructItems(array $data) : void
    {

        $this->elements = array();

        if (!$this->bean) return;

        //check if table name is same for the bean and iterator
        $iterator = $this->column->getView()->getIterator();

        //iterator is the same table as the bean
        if (strcmp($iterator->name(), $this->bean->getTableName()) == 0) {
            $item = new StorageItem();
            $item->className = get_class($this->bean);
            $item->id = $data[$this->bean->key()];

            $item->field = $this->blob_field;

            $this->elements[] = $item;
        }
        else {

            $fieldName = $this->column->getName();
            //debug("Column '$fieldName' Related bean: '" . get_class($this->bean) . "' Related field: '$this->relateField'", $data);

            if (array_key_exists($fieldName, $data)) {

                $values = $this->getDataValues($data[$fieldName]);
                foreach ($values as $idx => $value) {
                    $item = new StorageItem();
                    $item->className = get_class($this->bean);
                    $item->id = $value;
                    $this->elements[] = $item;
                }

            }

        }

    }

    protected function renderImageItems()
    {
        $num = count($this->elements);

        foreach ($this->elements as $idx => $item) {

            $this->image_popup->image()->setStorageItem($item);

            $this->image_popup->setClassName("Item");

            $this->image_popup->render();
        }
    }

    //default rendering fetch from source bean linking with current 'view' iterator prkey
    public function setData(array $data) : void
    {
        parent::setData($data);

        if (!$this->bean) {
            $this->bean = $this->column->getView()->getIterator()->bean();
        }

        try {
            $this->constructItems($data);
        }
        catch (Exception $e) {
            $this->setContents($e->getMessage());
            $this->imageList->setRenderEnabled(false);
            return;
        }

        $this->imageList->setRenderEnabled(true);

        if ($this->action) {
            $this->action->setData($data);
            $this->image_popup->setAttribute("href", $this->action->getURL()->toString());
        }
        else {
            $this->image_popup->removeAttribute("href");
        }

        if (isset($data["caption"])) {
            $this->image_popup->setTooltip($data["caption"]);
        }
        else {
            $this->image_popup->removeAttribute("caption");
        }

        $this->setContents("");
    }

}

?>
