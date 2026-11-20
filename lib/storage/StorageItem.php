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

    /**
     * The address of the image - each positive number an upper limit, the proportions kept (width: at most that wide,
     * height: at most that high, both equal: fits inside the square; none: the stored image). The storage answers
     * only the enabled sizes (ImageSizes) - another size gets a 301 to the next larger one.
     */
    public function hrefImage(int $width = 0, int $height = 0) : URL
    {
        $this->setType(StorageItem::TYPE_IMAGE);
        $this->buildURL();

        $this->url->add(new URLParameter("width", max(0, $width)));
        $this->url->add(new URLParameter("height", max(0, $height)));

        if (Spark::GetBoolean(Config::STORAGE_ITEM_SLUG)) {
            return $this->SlugURL();
        }
        return $this->url;
    }

    /**
     * The stored image as it is
     */
    public function hrefFull() : URL
    {
        return $this->hrefImage();
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
        if ($this->url->getVersion() !== "") {
            $data[URL::KEY_VERSION] = $this->url->getVersion();
            $url->add(new PathParameter(URL::KEY_VERSION, URL::KEY_VERSION, false));
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
        if ($this->id > 0 && Spark::ValidClassName($this->className)) {
            try {
                $this->url->setVersion(new StorageVersion($this->className, $this->id, $this->type == StorageItem::TYPE_IMAGE));
            }
            catch (Exception $e) {
                Debug::ErrorLog("StorageItem version: " . $e->getMessage());
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