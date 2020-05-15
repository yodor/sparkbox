<?php

class JSONResponse
{

    //   $ret = array("status"=>"error", "message"=>"", "name"=>"RatingPostResponse");
    const STATUS_ERROR = "error";
    const STATUS_OK = "OK";

    public $status = "error";
    public $message = "";
    public $name = "";

    //holds contents to be rendered
    // 	public $contents = "";

    public function __construct($name)
    {
        $this->status = JSONResponse::STATUS_ERROR;

        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;

    }

    public function response()
    {
        header("Pragma: no-cache");
        header("Expires: 0");
        echo json_encode(get_object_vars($this));
    }

}

?>