<?php
include_once("utils/url/IVersion.php");

/**
 * Version of a local static file (CSS, JS ...): base36 of its modification time.
 * The URL path is resolved below $_SERVER['DOCUMENT_ROOT'].
 */
class FileVersion implements IVersion
{
    protected string $path = "";

    /**
     * @param string $path URL path of the file - '/sparkfront/css/Action.css'
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function token() : string
    {
        if (!str_starts_with($this->path, "/") || str_starts_with($this->path, "//")) return "";

        $root = (string)($_SERVER["DOCUMENT_ROOT"] ?? "");
        if ($root === "") return "";

        $time = @filemtime($root . $this->path);
        if ($time === false) return "";

        return base_convert((string)$time, 10, 36);
    }
}
