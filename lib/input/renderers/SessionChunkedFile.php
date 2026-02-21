<?php
include_once("input/renderers/SessionUpload.php");
include_once("responders/json/ChunkedFileUploadResponder.php");

class SessionChunkedFile extends SessionUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input, new ChunkedFileUploadResponder());

        $this->input()->setAttribute("chunk_size", Spark::Get(Config::UPLOAD_MAX_FILESIZE));
    }

    protected function fillDetails(Container $details) : void
    {
        $limits = new Container(false);
        $limits->setComponentClass("Limits");
        $details->items()->append($limits);

        $max_slots = new LabelSpan("Available Slots", -1);
        $max_slots->setAttribute("field", "max_slots");
        $limits->items()->append($max_slots);

    }
}