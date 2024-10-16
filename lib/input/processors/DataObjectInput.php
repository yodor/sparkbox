<?php

class DataObjectInput extends InputProcessor
{
    protected DataObject $object;

    public function __construct(DataInput $input, DataObject $object)
    {
        parent::__construct($input);

        $this->object = $object;
        $this->object->setName($input->getName());
    }

    public function getDataObject()
    {
        return $this->object;
    }

    public function setTransactBean(DBTableBean $bean, int $max_items=-1)
    {
        throw new Exception("Setting transaction bean is not supported");
    }

    public function getTransactBean(): ?DBTableBean
    {
        return NULL;
    }

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key) : void
    {

    }

    public function afterCommit(BeanTransactor $transactor) : void
    {

    }

    public function transactValue(BeanTransactor $transactor) : void
    {
        $name = $this->input->getName();

        if ($this->object->getValue()) {
            debug("DataInput: '$name' - Transacting serialized version of DataObject: ".get_class($this->object));
            $transactor->appendValue($this->input->getName(), DBConnections::Open()->escape(serialize($this->object)));
        }
        else {
            debug("DataInput: '$name' - DataObject value is null");
        }

    }

    public function loadPostData(array $data) : void
    {
        parent::loadPostData($data);
        $this->object->setData($data);
    }

    public function loadBeanData(int $editID, DBTableBean $bean, array $data) : void
    {
        if (!isset($data[$this->input->getName()])) return;

        $object = $data[$this->input->getName()];

        $object = @unserialize($object);
        if (!($object instanceof DataObject)) {
            debug("Un-serialized object is not DataObject");
            return;
        }

        $this->object = $object;

        debug("Setting value of DataInput from DataObject value");
        $this->input->setValue($this->object->getValue());
    }

}
