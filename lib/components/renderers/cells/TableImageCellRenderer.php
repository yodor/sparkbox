<?php
include_once("lib/components/renderers/cells/TableCellRenderer.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/components/TableColumn.php");

class ImageItem
{
    public $item_id = -1;
    public $item_class = "";
}

class TableImageCellRenderer extends TableCellRenderer implements IPhotoRenderer
{

    protected $bean = "";
    //   const RENDER_CROP = 1;
    //   const RENDER_THUMB = 2;
    //
    protected $width = -1;
    protected $height = 64;
    protected $render_mode = IPhotoRenderer::RENDER_CROP;

    //   protected $render_mode;
    //   protected $thumb_width;
    //   protected $thumb_height;

    protected $list_limit = 0;

    protected $blob_field = "";

    protected $source_key = NULL;

    protected $items = array();

    public function setSourceIteratorKey($source_key)
    {
        $source_fields = $this->bean->getFields();
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

    public function setThumbnailSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setRenderMode($mode)
    {
        $this->render_mode = $mode;
    }

    public function getRenderMode()
    {
        return $this->render_mode;
    }

    public function getThumbnailWidth()
    {
        return $this->width;
    }

    public function getThumbnailHeight()
    {
        return $this->height;
    }

    protected $action = false;

    public function setAction(Action $action)
    {
        $this->action = $action;
    }


    public function __construct($bean, $render_mode = IPhotoRenderer::RENDER_CROP, $width = 48, $height = -1)
    {
        parent::__construct();

        //source
        $this->bean = $bean;
        $this->width = $width;
        $this->height = $height;
        $this->render_mode = $render_mode;
        $this->list_limit = 1;
    }

    protected function constructItems($row, TableColumn $tc)
    {
        $this->items = array();

        if (!$this->bean instanceof DBTableBean) throw new Exception("bean is not instance of DBTableBean");

        $bean_class = get_class($this->bean);
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

        while ($this->bean->fetchNext($pfrow)) {
            $photoID = $pfrow[$this->bean->key()];
            $item = new ImageItem();
            $item->item_id = $photoID;
            $item->item_class = get_class($this->bean);
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

        $blob_field = "";

        if ($this->blob_field) {
            $blob_field = "blob_field=" . $this->blob_field;
        }

        foreach ($this->items as $idx => $item) {

            $photoID = $item->item_id;
            $bean_class = $item->item_class;

            $img_tag = "";

            $width = $this->width;
            $height = $this->height;

            echo "<div class='TableCellImageItem' itemID='$photoID' itemClass='$bean_class'>";

            if ($this->render_mode == IPhotoRenderer::RENDER_CROP) {
                $img_tag = "<img src='" . SITE_ROOT . "storage.php?cmd=image_crop&height=$height&width=$width&class=$bean_class&id=$photoID&$blob_field'>";
            }
            else if ($this->render_mode == IPhotoRenderer::RENDER_THUMB) {

                $size = max($width, $height);

                $img_tag = "<img src='" . SITE_ROOT . "storage.php?cmd=image_thumb&size=$size&class=$bean_class&id=$photoID&$blob_field'>";
            }

            if ($this->action instanceof EmptyAction) {
                echo $img_tag;
            }
            else {
                if ($this->action) {
                    $href = $this->action->getHref($row);
                    echo "<a href='$href'>$img_tag</a>";
                }
                else {

                    echo "<a class='image_popup' href='" . SITE_ROOT . "storage.php?cmd=gallery_photo&class=$bean_class&id=$photoID&$blob_field'  >";
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
    public function renderCell($row, TableColumn $tc)
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