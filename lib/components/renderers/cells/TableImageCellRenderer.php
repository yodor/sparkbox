<?php
include_once("lib/components/renderers/cells/TableCellRenderer.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/components/TableColumn.php");
include_once("lib/storage/StorageItem.php");

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

    public function setPhotoSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth()
    {
        return $this->width;
    }

    public function getPhotoHeight()
    {
        return $this->height;
    }

    protected $action = false;

    public function setAction(Action $action)
    {
        $this->action = $action;
    }


    public function __construct($bean, $width = 48, $height = -1)
    {
        parent::__construct();

        //source
        $this->bean = $bean;
        $this->width = $width;
        $this->height = $height;

        $this->list_limit = 1;
    }

    protected function constructItems(array $row, TableColumn $tc)
    {
        $this->items = array();


        $source_fields = $this->bean->fields();

        $photoID = -1;

        $limit = " LIMIT 1";
        $list_limit = (int)$this->list_limit;
        //       echo $list_limit;

        if ($list_limit > 0) {
            $limit = " LIMIT $list_limit";
        }
        else {
            $limit = "";
        }

        $order_by = "";
        if ($this->bean->haveField("position")) {
            $order_by = " ORDER BY position ASC ";
        }

        $num = 0;
        try {
            //iterate source based on view's prkey
            $prkey = $tc->getView()->getIterator()->key();
            if (in_array($prkey, $source_fields)) {
                $num = $this->bean->startIterator("WHERE $prkey=" . $row[$prkey] . " " . $order_by . " " . $limit);
            }
            else {
                //check sources' prkey with row
                $prkey = $this->bean->key();
                if ($this->source_key) {
                    $prkey = $this->source_key;

                }

                $row_fields = array_keys($row);
                if (in_array($prkey, $row_fields)) {
                    $value = (int)$row[$prkey];
                    $num = $this->bean->startIterator("WHERE $prkey=$value " . $order_by . " " . $limit);
                }
                else {
                    //check assigned column value. this might be array also try exploding first
                    $row_value = $row[$tc->getFieldName()];

                    if ($row_value) {
                        $value = explode("|", $row_value);
                    }
                    else {
                        $value = (int)$row_value;

                    }
                    if (is_array($value) && count($value) > 0) {
                        $num = $this->bean->startIterator("WHERE $prkey IN (" . implode(",", $value) . ") " . $order_by . " " . $limit);
                    }
                    else {
                        $num = $this->bean->startIterator("WHERE $prkey=$value " . $order_by . " " . $limit);
                    }

                }
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
            echo $this->bean->getLastIteratorSQL();

        }



        $pfrow = array();
        while ($this->bean->fetchNext($pfrow)) {
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

    protected function renderImageItems(array $row, TableColumn $tc)
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
                    $href = $this->action->getHref($row);
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

    //default rendering fetch from source linking with current 'view' prkey
    public function renderCell(array &$row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();

        try {
            $this->constructItems($row, $tc);
            $this->renderImageItems($row, $tc);
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }

        $this->finishRender();
    }


}

?>