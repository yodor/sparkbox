<?php
include_once("iterators/ArrayDataIterator.php");

class ServerImagesBean extends ArrayDataIterator
{

    public function __construct()
    {

        $this->folder = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "gallery_root/images/";
        $this->key = "id";
        $this->value_key = "filename";

        parent::__construct();

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

            $width = $size["width"];
            $height = $size["height"];
            $mime = $size["mime"];
            $date_upload = date("Y-m-d H:m:i", filemtime($filename));

            $this->values[] = array($this->key => $idx, $this->value_key => $filename, "width" => $width, "height" => $height, "size" => filesize($filename), "mime" => $mime, "date_upload" => $date_upload);

        }


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