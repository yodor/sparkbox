<?php
include_once("iterators/ArrayDataIterator.php");

class GalleryImagesBean extends ArrayDataIterator
{


    public function __construct()
    {
        $this->initFolderLocation();

        $this->key = "id";
        $this->value_key = "filename";


        parent::__construct();
    }

    protected function initFolderLocation()
    {
        $this->folder = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "gallery_sparkfront/images/";
    }

    //databean
    protected function initFields()
    {
        $this->fields = array($this->key, $this->value_key, "width", "height", "mime", "size", "photo", "date_upload");
    }

    //arraydatabean
    protected function initValues()
    {

        $dir = $this->folder;


        $files = array();

        $dh = opendir($dir);
        if (!is_resource($dh)) throw new Exception("Could not open '$dir'");

        while (false !== ($filename = readdir($dh))) {
            if (is_dir($filename)) continue;
            if (strcmp($filename, ".") == 0) continue;
            if (strcmp($filename, "..") == 0) continue;

            $files[] = $filename;

        }

        sort($files);


        $this->values = array();

        foreach ($files as $idx => $filename) {
            $filename = $dir . DIRECTORY_SEPARATOR . $filename;
            $size = getimagesize($filename);

            //not image file
            if ($size === FALSE) continue;

            $width = $size[0];
            $height = $size[1];
            $mime = $size["mime"];
            $date_upload = date("Y-m-d H:m:i", filemtime($filename));

            $this->values[] = array($this->key => $idx, $this->value_key => $filename, "width" => $width, "height" => $height, "size" => filesize($filename), "mime" => $mime, "date_upload" => $date_upload);

        }

        debug("LoadedValues: ", $this->values);

    }

    public function getByID($id)
    {

        $row = parent::getByID($id);

        $filename = $row["filename"];

        if (!isset($row["photo"])) {
            $row["photo"] = file_get_contents($filename);
        }

        return $row;
    }
}

?>