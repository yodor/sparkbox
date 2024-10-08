<?php
include_once("objects/data/DataObject.php");
include_once("utils/url/URL.php");

class StorageItem extends DataObject implements JsonSerializable
{

    public $id = -1;
    public $className = "";
    public $field = "";

    protected bool $use_external = false;

    protected ?URL $url = null;

    const int TYPE_IMAGE = 1;
    const int TYPE_FILE = 2;

    protected $type = StorageItem::TYPE_IMAGE;

    public function __construct(int $id = -1, string $className = "", string $field = "")
    {
        parent::__construct();

        $this->id = $id;
        $this->className = $className;
        $this->field = $field;

    }

    public function setType(int $type) : void
    {
        $this->type = $type;
    }

    public function enableExternalURL(bool $mode) : void
    {
        $this->use_external = $mode;
    }

    public function hrefImage(int $width = -1, int $height = -1) : string
    {
        if ($width > 0 || $height > 0) {

            if ($width == $height) {
                return $this->hrefThumb($width);
            }
            return $this->hrefCrop($width, $height);
        }
        return $this->hrefFull();
    }

    public function hrefFull() : string
    {
        $this->setType(StorageItem::TYPE_IMAGE);
        $this->buildURL();
        $this->url->remove("width");
        $this->url->remove("height");
        $this->url->remove("size");
        return $this->url->toString();
    }

    public function hrefCrop($width, $height) : string
    {
        $this->setType(StorageItem::TYPE_IMAGE);
        $this->buildURL();
        $this->url->remove("size");
        $this->url->add(new URLParameter("width", $width));
        $this->url->add(new URLParameter("height", $height));
        return $this->url->toString();

    }

    public function hrefThumb($width) : string
    {
        $this->setType(StorageItem::TYPE_IMAGE);
        $this->buildURL();
        $this->url->remove("width");
        $this->url->remove("height");
        $this->url->add(new URLParameter("size", $width));
        return $this->url->toString();
    }

    public function hrefFile() : string
    {
        $this->setType(StorageItem::TYPE_FILE);
        $this->buildURL();
        return $this->url->toString();
    }

    public function href() : string
    {
        $this->buildURL();
        return $this->url->toString();
    }

    protected function buildURL()
    {
        $cmd = "image";
        if ($this->type == StorageItem::TYPE_IMAGE) {
            $cmd = "image";
        }
        else if ($this->type == StorageItem::TYPE_FILE) {
            $cmd = "data";
        }

        if (is_null($this->url)) {
            $storageURL = STORAGE_LOCAL;
            if ($this->use_external) $storageURL = STORAGE_EXTERNAL;

            $this->url = new URL($storageURL);
        }

        $this->url->add(new URLParameter("cmd", $cmd));
        $this->url->add(new URLParameter("class", $this->className));
        $this->url->add(new URLParameter("id", (string)$this->id));

        if ($this->field) {
            $this->url->add(new URLParameter("field", $this->field));
        }
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

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->id = (int)$this->value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize() : mixed
    {
        return get_object_vars($this);

    }
}

?>
