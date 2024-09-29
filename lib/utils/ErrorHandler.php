<?php
class ErrorHandler extends Exception
{

    public function __construct(int $errNo, string $errStr, string $errFile, int $errLine) {
        parent::__construct();
        $this->message = $errStr;
        $this->code = $errNo;
        $this->file = $errFile;
        $this->line = $errLine;
    }

}
?>