<?php

#[AllowDynamicProperties] class JSONResponse extends SparkObject
{
    const string STATUS_ERROR = "error";
    const string STATUS_OK = "OK";

    public string $status = JSONResponse::STATUS_ERROR;
    public string $message = "";
    public string $name = "";

    /**
     * Seconds browsers and the CDN may keep a successful response - 0: not cached (default).
     * Only for responses that are the same for every visitor; the session cookie is not sent with them.
     * Not part of the JSON output.
     * @var int
     */
    protected int $maxAge = 0;

    public function __construct(string $name)
    {
        parent::__construct();
        $this->status = JSONResponse::STATUS_ERROR;
        $this->name = $name;
    }

    /**
     * Let browsers and the CDN keep a successful response for '$seconds' - see $maxAge
     * @param int $seconds
     * @return void
     */
    public function setMaxAge(int $seconds) : void
    {
        $this->maxAge = max(0, $seconds);
    }

    public function sendHeaders() : void
    {
        if ($this->maxAge > 0 && strcmp($this->status, JSONResponse::STATUS_OK) === 0) {
            //shared by all visitors - never cache a response that sets a cookie
            header_remove("Set-Cookie");
            header("Cache-Control: public, max-age=" . $this->maxAge);
        }
        else {
            header("Cache-Control: no-cache");
            header("Expires: 0");
        }
        header("Content-Type: application/json");
    }

    public function send() : void
    {
        $this->sendHeaders();
        echo json_encode($this);
    }

}