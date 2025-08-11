<?php
include_once("objects/data/DataObject.php");
include_once("utils/url/URL.php");

class StorageItem extends DataObject implements JsonSerializable
{

    public int $id = -1;
    public string $className = "";
    public string $field = "";

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
        //$this->use_external = $mode;
    }

    public function hrefImage(int $width = 0, int $height = 0) : URL
    {
        if ($width < 0) $width = 0;
        if ($height < 0) $height = 0;

        if ($width > 0 || $height > 0) {

            if ($width == $height) {
                return $this->hrefThumb($width);
            }
            return $this->hrefCrop($width, $height);
        }
        //full version
        return $this->hrefCrop(0, 0);
    }

    public function hrefFull() : URL
    {
          return $this->hrefCrop(0, 0);
    }
    public function hrefThumb(int $width) : URL
    {
        return $this->hrefCrop($width, $width);
    }

    public function hrefCrop(int $width, int $height) : URL
    {
        if ($width < 0) $width = 0;
        if ($height < 0) $height = 0;

        $this->setType(StorageItem::TYPE_IMAGE);
        $this->buildURL();

        $this->url->add(new URLParameter("width", $width));
        $this->url->add(new URLParameter("height", $height));

        if (STORAGE_ITEM_SLUG) {
            return $this->SlugURL();
        }
        else {
            return $this->url;
        }

    }

    private function SlugURL() : URL
    {
        $url = new URL(LOCAL . "/assets/");

        $data = array("cmd"=>"", "class"=>"", "id"=>"", "width"=>"", "height"=>"");
        foreach ($data as $key=>$value) {
            if ($this->url->contains($key)) {
                $data[$key] = $this->url->get($key)->value();
                $url->add(new PathParameter($key, $key, false));
            }
        }
        $url->setData($data);

        return $url;
    }
    public function hrefFile() : URL
    {
        $this->setType(StorageItem::TYPE_FILE);
        $this->buildURL();
        if (STORAGE_ITEM_SLUG) {
            return $this->SlugURL();
        }
        else {
            return $this->url;
        }
    }

    public function href() : URL
    {
        $this->buildURL();
        return $this->url;
    }

    protected function buildURL() : void
    {
        $cmd = "image";
        if ($this->type == StorageItem::TYPE_IMAGE) {
            $cmd = "image";
        }
        else if ($this->type == StorageItem::TYPE_FILE) {
            $cmd = "data";
        }

        $this->url = new URL(STORAGE_LOCAL);

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
