<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/TableColumn.php");
include_once("storage/StorageItem.php");

class TableImageCellRenderer extends TableCellRenderer implements IPhotoRenderer
{

    /**
     * @var DBTableBean
     */
    protected $bean = NULL;

    protected $list_limit = 0;

    protected $blob_field = "";

    protected $relateField = "";

    protected $items = array();

    protected $action = NULL;

    protected $image_popup = NULL;

    protected static $DefaultWidth = 128;
    protected static $DefaultHeight = -1;

    protected $sortable = FALSE;

    public static function SetDefaultPhotoSize(int $width, int $height)
    {
        TableImageCellRenderer::$DefaultWidth=$width;
        TableImageCellRenderer::$DefaultHeight=$height;
    }

    public function __construct(int $width = -1, int $height = -1)
    {
        parent::__construct();

        $this->list_limit = 1;

        if ($width<1 && $height<1) {
            $width = TableImageCellRenderer::$DefaultWidth;
            $height = TableImageCellRenderer::$DefaultHeight;
        }

        $this->image_popup = new ImagePopup();
        $this->image_popup->setPhotoSize($width, $height);

    }

    public function setBean(DBTableBean $bean, string $relateField = "")
    {
        $this->bean = $bean;
        if ($relateField) {
            $this->relateField = $relateField;
        }

    }

    public function setLimit(int $num)
    {
        $this->list_limit = (int)$num;
    }

    public function setBlobField(string $blob_field)
    {
        $this->blob_field = $blob_field;
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->image_popup->setPhotoSize($width, $height);
    }

    public function getPhotoWidth(): int
    {
        return $this->image_popup->getPhotoWidth();
    }

    public function getPhotoHeight(): int
    {
        return $this->image_popup->getPhotoHeight();
    }

    public function setAction(Action $action)
    {
        $this->action = $action;
    }

    protected function constructItems(array &$data)
    {

        $this->items = array();

        if (!$this->bean) return;

        //check if table name is same for the bean and iterator
        $iterator = $this->column->getView()->getIterator();

        //iterator is the same table as the bean
        if (strcmp($iterator->name(), $this->bean->getTableName()) == 0) {
            $item = new StorageItem();
            $item->className = get_class($this->bean);
            $item->id = $data[$this->bean->key()];
            $item->field = $this->blob_field;
            $this->items[] = $item;
        }
        else {

            $fieldName = $this->column->getFieldName();

            debug("Column '$fieldName' Related bean: '" . get_class($this->bean) . "' Related field: '$this->relateField'");

            $relate_key = $this->bean->key();
            if ($this->relateField) {
                $relate_key = $this->relateField;
            }

            $qry = $this->bean->query();
            $qry->select->fields = $this->bean->key();
            if ($this->list_limit > 0) {
                $qry->select->limit = $this->list_limit;
            }
            if ($this->bean->haveField("position")) {
                $qry->select->order_by = " position ASC ";
            }

            //if result row contains key named by the column field name then we use the values as primary key values of the bean
            //else we match reversely if the bean primary key name is found as key in the result row we use this
            //else try iterators' primary key and value as relation value access
            $values = "";

            if (isset($data[$fieldName])) {
                debug("Using column field '$fieldName' as data key");
                $values = $data[$fieldName];
            }
            else if (isset($data[$relate_key])) {
                debug("Using bean field '$relate_key' as data key");
                $values = $data[$relate_key];
            }
            else {
                debug("Using iterator key '$relate_key' as data key");
                $relate_key = $this->column->getView()->getIterator()->key();
                $values = $data[$relate_key];
            }

            if (!$values) return;

            debug("Using '$relate_key' as bean access key");

            $values = explode("|", $values);
            $qry->select->where = " $relate_key IN ( " . implode(",", $values) . " )";

            $qry->exec();

            while ($result = $qry->next()) {
                $photoID = $result[$this->bean->key()];

                $item = new StorageItem();
                $item->id = $photoID;
                $item->className = get_class($this->bean);
                $item->field = $this->blob_field;

                $this->items[] = $item;
            }

        }

    }

    protected function renderImageItems()
    {
        $num = count($this->items);

        echo "<div class='ImageList'  count='$num'>";

        foreach ($this->items as $idx => $item) {

            $this->image_popup->setStorageItem($item);

            $this->image_popup->setClassName("Item");

            $this->image_popup->render();
        }

        echo "</div>"; //ImageList

    }

    protected function renderImpl()
    {

        $this->renderImageItems();

    }

    //default rendering fetch from source bean linking with current 'view' iterator prkey
    public function setData(array &$row)
    {
        parent::setData($row);

        if (!$this->bean) {
            $this->bean = $this->column->getView()->getIterator()->bean();
        }

        $this->constructItems($row);

        if ($this->action) {
            $this->image_popup->setAttribute("href", $this->action->getHref($row));
        }
        else {
            $this->image_popup->clearAttribute("href");
        }

        if (isset($row["caption"])) {
            $this->image_popup->setAttribute("caption", $row["caption"]);
        }
        else {
            $this->image_popup->clearAttribute("caption");
        }

    }

}

?>