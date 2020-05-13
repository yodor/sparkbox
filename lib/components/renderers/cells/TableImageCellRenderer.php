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

    protected $width = -1;
    protected $height = 64;

    protected $list_limit = 0;

    protected $blob_field = "";

    protected $relateField = "";

    protected $items = array();

    protected $action = NULL;

    protected $image_popup = NULL;

    public function __construct(int $width = 48, int $height = -1)
    {
        parent::__construct();

        $this->width = $width;
        $this->height = $height;

        $this->list_limit = 1;
    }

    public function setBean(DBTableBean $bean, string $relateField="")
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
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;
    }

    protected function constructItems(array &$data)
    {
        $this->items = array();

        if ($this->bean instanceof DBTableBean) {
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

                $relate_key = $iterator->key();
                if ($this->relateField) {
                    $relate_key = $this->relateField;
                }

                $relate_value = $data[$relate_key];

                $qry = $this->bean->queryField($relate_key, $relate_value, $this->list_limit);
                $qry->select->fields = $this->bean->key();
                if ($this->bean->haveField("position")) {
                    $qry->select->order_by = " position ASC ";
                }

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
        else {
            echo "No bean set";
        }
    }
    //    protected function constructItems(array &$data)
    //    {
    //        $this->items = array();
    //
    //        if (!$this->blob_field) {
    //            $this->blob_field = $this->column->getFieldName();
    //        }
    //
    //        $source_fields = $this->bean->fields();
    //
    //        $qry = $this->bean->query();
    //
    //        $qry->select->fields = $this->bean->key();
    //
    //        if ((int)$this->list_limit > 0) {
    //            $qry->select->limit = $this->list_limit;
    //        }
    //
    //        if ($this->bean->haveField("position")) {
    //            $qry->select->order_by = " position ASC ";
    //        }
    //
    //        $num = 0;
    //        try {
    //            //iterate source based on view's prkey
    //            $prkey = $this->column->getView()->getIterator()->key();
    //            if (in_array($prkey, $source_fields)) {
    //                $qry->select->where = "$prkey={$this->data[$prkey]}";
    //            }
    //            else {
    //                //check sources' prkey with row
    //                $prkey = $this->bean->key();
    //                if ($this->source_key) {
    //                    $prkey = $this->source_key;
    //                }
    //
    //                $row_fields = array_keys($this->data);
    //                if (in_array($prkey, $row_fields)) {
    //                    $value = (int)$this->data[$prkey];
    //                    $qry->select->where = "$prkey=$value";
    //                }
    //                else {
    //                    //check assigned column value. this might be array also try exploding first
    //                    $row_value = $this->data[$this->column->getFieldName()];
    //
    //                    if ($row_value) {
    //                        $value = explode("|", $row_value);
    //                    }
    //                    else {
    //                        $value = (int)$row_value;
    //                    }
    //                    if (is_array($value) && count($value) > 0) {
    //                        $qry->select->where = " $prkey IN (" . implode(",", $value) . ") ";
    //                    }
    //                    else {
    //                        $qry->select->where = " $prkey=$value ";
    //                    }
    //
    //                }
    //            }
    //            $num = $qry->exec();
    //        }
    //        catch (Exception $e) {
    //            echo $e->getMessage();
    //            echo $qry->select->getSQL();
    //        }
    //
    //        while ($pfrow = $qry->next()) {
    //            $photoID = $pfrow[$this->bean->key()];
    //            $item = new StorageItem();
    //            $item->id = $photoID;
    //            $item->className = get_class($this->bean);
    //            $item->field = $this->blob_field;
    //
    //            $this->items[] = $item;
    //        }
    //    }

    protected function renderImageItems()
    {
        $num = count($this->items);

        if ($num < 1) {
            // 		echo "N/A";

        }
        if ($num > 1) {
            echo "<div class='TableCellImageList'  count='$num'>";
        }

        foreach ($this->items as $idx => $item) {

            $photoID = $item->id;
            $bean_class = $item->className;

            echo "<div class='TableCellImageItem' itemID='$photoID' itemClass='$bean_class'>";

            $img_tag = "<img src='{$item->hrefImage($this->width, $this->height)}'>";

            if ($this->action instanceof EmptyAction) {
                echo $img_tag;
            }
            else {
                if ($this->action) {
                    $href = $this->action->getHref($this->data);
                    echo "<a href='$href'>$img_tag</a>";
                }
                else {

                    echo "<a class='ImagePopup' href='{$item->hrefFull()}'  >";
                    echo $img_tag;
                    echo "</a>";
                }
            }

            echo "</div>"; //TableCellImageItem

        }

        if ($num > 1) {
            echo "</div>"; //TableCellImageList
        }
    }

    protected function renderImpl()
    {

        $this->renderImageItems();

    }

    //default rendering fetch from source bean linking with current 'view' iterator prkey
    public function setData(array &$row)
    {
        parent::setData($row);
        $this->constructItems($row);

    }

}

?>