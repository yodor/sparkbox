<?php
class DataBuffer
{
    protected string $data = "";
    protected string $mime = "";
    protected int $length = 0;
    protected finfo $finfo;

    public function __construct()
    {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->setData("");
    }

    public function base64() : string
    {
        return base64_encode($this->data);
    }

    public function data() : string
    {
        return $this->data;
    }


    public function setData(string $data) : void
    {
        $this->data = $data;
        $this->mime = $this->finfo->buffer($this->data);
        $this->length = strlen($this->data);
    }

    public function length() : int
    {
        return $this->length;
    }

    public function mime() : string
    {
        return $this->mime;
    }

}
?>
