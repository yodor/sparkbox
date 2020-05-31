<?php

class DataObjectInput extends InputProcessor
{
    protected $object;

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

    public function setTransactBean(DBTableBean $bean)
    {
        throw new Exception("Setting transaction bean is not supported");
    }

    public function getTransactBean(): ?DBTableBean
    {
        return NULL;
    }

    public function beforeCommit(BeanTransactor $transactor, DBDriver $db, string $item_key)
    {

    }

    public function afterCommit(BeanTransactor $transactor)
    {

    }

    public function transactValue(BeanTransactor $transactor)
    {
        $name = $this->input->getName();

        if ($this->object->getValue()) {
            debug("DataInput: '$name' - Transacting serialized version of DataObject: ".get_class($this->object));
            $transactor->appendValue($this->input->getName(), DBConnections::Get()->escape(serialize($this->object)));
        }
        else {
            debug("DataInput: '$name' - DataObject value is null");
        }

    }

    public function loadPostData(array &$data)
    {
        parent::loadPostData($data);
        $this->object->setData($data);
    }

    public function loadBeanData(int $editID, DBTableBean $bean, array &$item_row)
    {
        if (!isset($item_row[$this->input->getName()])) return;

        $object = $item_row[$this->input->getName()];

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