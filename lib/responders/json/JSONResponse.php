<?php

#[AllowDynamicProperties] class JSONResponse extends SparkObject
{
    const string STATUS_ERROR = "error";
    const string STATUS_OK = "OK";

    public string $status = JSONResponse::STATUS_ERROR;
    public string $message = "";
    public string $name = "";

    public function __construct(string $name)
    {
        parent::__construct();
        $this->status = JSONResponse::STATUS_ERROR;
        $this->name = $name;
    }

    public function send()
    {
        header("Cache-Control: no-cache");
        //header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Type: application/json");
        echo json_encode($this);
    }

}

?>
