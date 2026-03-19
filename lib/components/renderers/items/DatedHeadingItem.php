<?php
include_once("components/Action.php");
include_once("beans/DatedBean.php");
include_once("components/LabelSpan.php");
include_once("components/ImageStorage.php");

class DatedHeadingItem extends Action implements IDataResultConsumer
{

    /**
     * ICU date format
     * @var string
     */
    protected string $dateFormat = "d MMMM y";

    protected ?ImageStorage $thumbnail = null;
    protected ?LabelSpan $details = null;

    protected ?DatedBean $bean = null;

    public function __construct(DatedBean $bean, URL $moduleURL)
    {
        parent::__construct();

        $this->bean = $bean;


        $this->setURL(new URL($moduleURL->toString()));
        $this->getURL()->add(new DataParameter($this->bean->key()));

        $this->setClassName("item");

        $this->thumbnail = new ImageStorage();
        $this->thumbnail->setComponentClass("thumbnail");

        $this->thumbnail->image()->getStorageItem()->className = get_class($bean);
        $this->thumbnail->image()->setPhotoSize(64,64);

        $this->details = new LabelSpan();
        $this->details->setComponentClass("details");

        $this->details->label()->addClassName("title");
        $this->details->span()->addClassName("date");

        $this->items()->append($this->thumbnail);
        $this->items()->append($this->details);

    }

    /**
     * Can change thumbnail size
     * @return ImageStorage
     */
    public function getThumbnail() : ImageStorage
    {
        return $this->thumbnail;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->details->label()->setContents($data["item_title"]);
        $this->details->span()->setContents($this->bean->formatDate(strtotime($data["item_date"]), $this->dateFormat));

        $this->thumbnail->setData($data);
        $this->thumbnail->setID($this->id);

    }


    public function setDateFormat(string $dateFormat) : void
    {
        $this->dateFormat = $dateFormat;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function collectDataKeys(): array
    {
        return ["item_title", "item_date"];
    }
}