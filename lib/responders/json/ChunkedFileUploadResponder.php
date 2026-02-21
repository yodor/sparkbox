<?php
include_once("responders/json/ChunkedUploadControlResponder.php");
include_once("input/validators/FileUploadValidator.php");
include_once("input/renderers/InputField.php");

class ChunkedFileUploadResponder extends ChunkedUploadControlResponder
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHTML(StorageObject $object, string $field_name) : string
    {

        if (!($object instanceof FileStorageObject)) throw new Exception("Expecting FileStorageObject");

        $cacheFile = $this->getCacheEntry($object->UID())->getFile();
        $filename = $object->getFilename();
        $mime = $cacheFile->getMIME();
        $uid = $object->UID();

        Debug::ErrorLog("UID:$uid filename:$filename mime:$mime");

        ob_start();

        $element = new Container(false);
        $element->setComponentClass("Element");
        $element->setTooltip($filename);

        $thumbnail = new Container(false);
        $thumbnail->setComponentClass("Thumbnail");
        $element->items()->append($thumbnail);

        $image = new Image();
        $image->setAttribute("src", Spark::Get(Config::SPARK_LOCAL) . "/images/mimetypes/generic.png");
        $thumbnail->items()->append($image);

        $info = new Container(false);
        $info->setComponentClass("info");
        $element->items()->append($info);

        $item = new LabelSpan();
        $item->setComponentClass("item");
        $item->addClassName("filename");
        $item->label()->setContents(tr("Name"));
        $item->span()->setContents($filename);
        $info->items()->append($item);

        $item = new LabelSpan();
        $item->setComponentClass("item");
        $item->addClassName("filename");
        $item->label()->setContents(tr("Size"));
        $item->span()->setContents(Spark::ByteLabel($cacheFile->length()));
        $info->items()->append($item);

        $button = new Component(false);
        $button->setComponentClass("remove_button");
        $button->setAttribute("action", "Remove");

        $element->items()->append($button);

        $input = new Input("hidden", "uid_{$field_name}[]", $uid);
        $element->items()->append($input);

        $element->render();

        $html = ob_get_contents();

        ob_end_clean();

        return $html;
    }

    public function validator() : UploadDataValidator
    {
        return new FileUploadValidator();
    }

}