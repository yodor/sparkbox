<?php
include_once("objects/data/DataObject.php");
include_once("utils/url/URL.php");
include_once("storage/StorageVersion.php");

class StorageItem extends DataObject implements JsonSerializable
{

    public int $id = -1;
    public string $className = "";

    protected ?URL $url = null;

    const int TYPE_IMAGE = 1;
    const int TYPE_FILE = 2;

    protected int $type = StorageItem::TYPE_IMAGE;

    public function __clone()
    {
        if ($this->url instanceof URL) {
            $this->url = clone $this->url;
        }
    }

    public function __construct(int $id = -1, string $className = "")
    {
        parent::__construct();

        $this->id = $id;
        $this->className = $className;
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

        if (Spark::GetBoolean(Config::STORAGE_ITEM_SLUG)) {
            return $this->SlugURL();
        }
        else {
            return $this->url;
        }
    }

    private function SlugURL() : URL
    {
        $url = new URL(Spark::Get(Config::LOCAL) . "/assets/");

        $data = array("cmd"=>"", "class"=>"", "id"=>"", "width"=>"", "height"=>"");
        foreach ($data as $key=>$value) {
            if ($this->url->contains($key)) {
                $data[$key] = $this->url->get($key)->value();
                $url->add(new PathParameter($key, $key, false));
            }
        }
        $filename = "";
        if ($this->getName()) {
            $filename = Spark::Slugify($this->getName());
        }
        else {
            $filename = $this->className;
        }
        //images: extension of the configured scaler output format
        //files: no extension - the real one is not known here, storage.php adds it to the
        //Content-Disposition filename from the content MIME type
        $data["name"] = $filename.".".$this->id;
        if ($this->type == StorageItem::TYPE_IMAGE) {
            $extension = Spark::MimeExtension((string)Spark::Get(Config::IMAGE_SCALER_OUTPUT_FORMAT));
            $data["name"] .= "." . ($extension ?: "webp");
        }

        //version token as the last path segment before the file name: .../<v>/<name>.<id>.webp
        //BeanDataResponse reads it back from the route
        if ($this->url->contains(StorageVersion::KEY)) {
            $data[StorageVersion::KEY] = $this->url->get(StorageVersion::KEY)->value();
            $url->add(new PathParameter(StorageVersion::KEY, StorageVersion::KEY, false));
        }

        $url->add(new PathParameter("name", "name",false, false));
        $url->setData($data);

        return $url;
    }
    public function hrefFile() : URL
    {
        $this->setType(StorageItem::TYPE_FILE);
        $this->buildURL();
        if (Spark::GetBoolean(Config::STORAGE_ITEM_SLUG)) {
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

        $this->url = new URL(Spark::Get(Config::STORAGE_URL));

        $this->url->add(new URLParameter("cmd", $cmd));
        $this->url->add(new URLParameter("class", $this->className));
        $this->url->add(new URLParameter("id", (string)$this->id));

        //version token - URL changes exactly when the row data changes, allows 'immutable' caching
        if ($this->id > 0) {
            $token = StorageVersion::Token($this->className, $this->id, ($this->type == StorageItem::TYPE_IMAGE));
            if ($token !== "") {
                $this->url->add(new URLParameter(StorageVersion::KEY, $token));
            }
        }


    }

    public static function Create(int $id, $className) : StorageItem
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

    public static function Image(int $id, $className, int $width = -1, int $height = -1) : URL
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