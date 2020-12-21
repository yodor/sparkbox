<?php

class StorageItem
{

    public $id = -1;
    public $className = "";
    public $field = "";
    public $storageURL = "";

    public function __construct(int $id = -1, string $className = "", string $field = "")
    {
        $this->id = $id;
        $this->className = $className;
        $this->field = $field;
        $this->storageURL = STORAGE_LOCAL;
    }

    public function enableExternalURL(bool $mode)
    {
        if ($mode) {
            $this->storageURL = STORAGE_EXTERNAL;
        }
        else {
            $this->storageURL = STORAGE_LOCAL;
        }
    }

    public function hrefImage(int $width = -1, int $height = -1)
    {
        if ($width > 0 || $height > 0) {

            if ($width == $height) {
                return $this->hrefThumb($width);
            }
            return $this->hrefCrop($width, $height);
        }
        return $this->hrefFull();
    }

    public function hrefFull()
    {
        return $this->storageURL . "?cmd=image&" . $this->getParameters();
    }

    public function hrefCrop($width, $height)
    {
        return $this->storageURL . "?cmd=image&" . $this->getParameters() . "&width=$width&height=$height";
    }

    public function hrefThumb($width)
    {
        return $this->storageURL . "?cmd=image&" . $this->getParameters() . "&size=$width";
    }

    public function hrefFile()
    {
        return $this->storageURL . "?cmd=data&" . $this->getParameters();
    }

    public function getParameters()
    {
        $ret = "id={$this->id}&class={$this->className}";
        if ($this->field) {
            $ret .= "&field={$this->field}";
        }
        return $ret;
    }

    public static function Create(int $id, $className)
    {
        $item = new StorageItem();
        $item->id = $id;
        if (is_object($className)) {
            $className = get_class($className);
        }
        else if (strlen($className) < 1) {
            throw new Exception("Classname required");
        }
        $item->className = $className;

        return $item;
    }

    public static function Image(int $id, $className, int $width = -1, int $height = -1)
    {
        $item = StorageItem::Create($id, $className);
        return $item->hrefImage($width, $height);
    }

}

?>
