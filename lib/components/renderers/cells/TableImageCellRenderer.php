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
    protected $bean = null;

    protected $width = -1;
    protected $height = 64;

    protected $list_limit = 0;

    protected $blob_field = "";

    protected $source_key = NULL;

    protected $items = array();

    protected $action = null;


    protected $data = null;

    public function __construct(DBTableBean $bean, int $width = 48, int $height = -1)
    {
        parent::__construct();

        //source
        $this->bean = $bean;
        $this->width = $width;
        $this->height = $height;

        $this->list_limit = 1;
    }

    public function setSourceIteratorKey($source_key)
    {
        $source_fields = $this->bean->fields();
        if (!in_array($source_key, $source_fields)) throw new Exception("Source fields does not contain interation key '$source_key'");
        $this->source_key = $source_key;
    }

    public function setListLimit($num)
    {
        $this->list_limit = (int)$num;
    }

    public function setBlobField($blob_field)
    {
        $this->blob_field = $blob_field;
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth() : int
    {
        return $this->width;
    }

    public function getPhotoHeight() : int
    {
        return $this->height;
    }

    public function setAction(Action $action)
    {
        $this->action = $action;
    }

    protected function constructItems()
    {
        $this->items = array();

        $source_fields = $this->bean->fields();

        $qry = $this->bean->query();

        $qry->select->fields = $this->bean->key();

        if ((int)$this->list_limit > 0) {
            $qry->select->limit = $this->list_limit;
        }

        if ($this->bean->haveField("position")) {
            $qry->select->order_by = " position ASC ";
        }

        $num = 0;
        try {
            //iterate source based on view's prkey
            $prkey = $this->column->getView()->getIterator()->key();
            if (in_array($prkey, $source_fields)) {
                $qry->select->where = "$prkey={$this->data[$prkey]}";
            }
            else {
                //check sources' prkey with row
                $prkey = $this->bean->key();
                if ($this->source_key) {
                    $prkey = $this->source_key;
                }

                $row_fields = array_keys($this->data);
                if (in_array($prkey, $row_fields)) {
                    $value = (int)$this->data[$prkey];
                    $qry->select->where = "$prkey=$value";
                }
                else {
                    //check assigned column value. this might be array also try exploding first
                    $row_value = $this->data[$this->column->getFieldName()];

                    if ($row_value) {
                        $value = explode("|", $row_value);
                    }
                    else {
                        $value = (int)$row_value;
                    }
                    if (is_array($value) && count($value) > 0) {
                        $qry->select->where = " $prkey IN (" . implode(",", $value) . ") ";
                    }
                    else {
                        $qry->select->where = " $prkey=$value ";
                    }

                }
            }
            $num = $qry->exec();
        }
        catch (Exception $e) {
            echo $e->getMessage();
            echo $qry->select->getSQL();
        }

        while ($pfrow = $qry->next()) {
            $photoID = $pfrow[$this->bean->key()];
            $item = new StorageItem();
            $item->id = $photoID;
            $item->className = get_class($this->bean);

            if ($this->blob_field) {
                $item->field = $this->blob_field;
            }
            $this->items[] = $item;
        }
    }

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

                    echo "<a class='image_popup' href='{$item->hrefFull()}'  >";
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
        try {
            $this->constructItems();
            $this->renderImageItems();
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    //default rendering fetch from source linking with current 'view' prkey
    public function setData(array &$row)
    {
        parent::setData($row);
        $this->data = $row;

    }


}

?>