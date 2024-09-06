<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");

class StorageItemCellRenderer extends TableCellRenderer
{

    protected $action;
    protected $beanField = "";
    protected $beanClass = "";
    protected $idField = "";
    protected $id = -1;

    public function __construct()
    {
        parent::__construct();

        $this->action = new Action();

    }

    public function setBeanField(string $field)
    {
        $this->beanField = $field;
    }

    public function setBeanClass(string $beanClass)
    {
        $this->beanClass = $beanClass;
    }

    public function setIdField(string $idField)
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
    }

    protected function renderImpl()
    {

        $id = $this->id;

        $beanClass = $this->beanClass;
        $beanField = $this->beanField;

        if (!$beanClass) {

            throw new Exception("Bean class not set");

        }

        if (!$beanField) {

            throw new Exception("Bean field not set");

        }

        if ($id < 0) {
            throw new Exception("Invalid bean ID");
        }

        $si = new StorageItem($id, $beanClass, $beanField);

        $this->action->getURL()->fromString($si->hrefFile());
        $this->action->setContents(tr("Download"));
        $this->action->render();

    }

}

?>
