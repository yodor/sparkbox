<?php
class DataBuffer
{
    protected string $data = "";

    public function __construct()
    {

    }
    public function setRef(string &$data)
    {
        $this->data = $data;
    }
    public function &getRef() : string
    {
        return $this->data;
    }

    public function getData() : string
    {

        return $this->data;
    }

    public function setData(string $data) : void
    {
        $this->data = $data;
    }

    public function length() : int
    {
        return strlen($this->data);
    }

    public function mime() : string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($this->data);
    }

}
?>
