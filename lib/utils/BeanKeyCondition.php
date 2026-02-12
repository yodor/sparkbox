<?php
include_once("beans/DBTableBean.php");
include_once("utils/RequestParameterCondition.php");
/**
 * Check if request contains the primary key of a DBTableBean
 *
 * Class RequestBeanKey
 *
 */
class BeanKeyCondition extends RequestParameterCondition
{

    protected int $id = -1;
    protected array $data = array();

    protected URLParameter $urlparam;

    protected DBTableBean $bean;

    protected array $fields = array();

    /**
     * Will fetch the bean fields specified in the $fields array parameter that can be accessed later from getData
     *
     * BeanKeyCondition constructor.
     * @param DBTableBean $bean
     * @param string $redirectURL
     * @param array $fields
     * @throws Exception
     */
    public function __construct(DBTableBean $bean, string $redirectURL, array $fields = array())
    {

        $this->bean = $bean;

        $this->fields = $fields;

        parent::__construct($bean->key(), $redirectURL);

    }

    protected function process() : void
    {
        parent::process();

        $this->id = (int)$this->value;

        $this->data = $this->bean->getByID($this->id, ...$this->fields);

        $this->urlparam = new URLParameter($this->bean->key(), $this->id);
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    public function getData(string $name)
    {
        return $this->data[$name];
    }



    /**
     * Return the URLParameter ie getURLParamter()->text() would return 'glrID=1'
     * @return URLParameter
     */
    public function getURLParameter(): URLParameter
    {
        return $this->urlparam;
    }
}