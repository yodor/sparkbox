<?php
include_once("components/renderers/cells/TableCell.php");
include_once("components/Action.php");

class StorageItemCell extends TableCell
{

    protected string $beanField = "";
    protected string $beanClass = "";
    protected string $idField = "";
    protected int $id = -1;

    public function __construct()
    {
        parent::__construct();

        $this->action = new Action();

        $this->addClassName("StorageItem");

        $this->items()->append($this->action);

    }

    public function setBeanField(string $field): void
    {
        $this->beanField = $field;
    }

    public function setBeanClass(string $beanClass): void
    {
        $this->beanClass = $beanClass;
    }

    public function setIdField(string $idField): void
    {
        $this->idField = $idField;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->id = -1;
        if (isset($data[$this->idField])) {
            $this->id = (int)$data[$this->idField];
        }

        if (!$this->beanClass) {
            throw new Exception("Bean class not set");
        }

        if (!$this->beanField) {
            throw new Exception("Bean field not set");
        }

        if ($this->id < 0) {
            throw new Exception("Invalid bean ID");
        }

        $si = new StorageItem($this->id, $this->beanClass, $this->beanField);

        $this->action->getURL()->fromString($si->hrefFile());
        $this->action->setContents(tr("Download"));
        $this->setContents("");
    }

}