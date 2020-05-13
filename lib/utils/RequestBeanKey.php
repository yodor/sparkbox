<?php
include_once("beans/DBTableBean.php");

//TODO:Check Usage
class RequestBeanKey
{

    protected $id;
    protected $data;
    protected $query;

    protected $urlparam;

    public function __construct(DBTableBean $bean, string $redirectURL, array $fields = array())
    {

        try {
            $this->bean = $bean;

            if (!isset($_GET[$bean->key()])) throw new Exception("Key: {$bean->key()} not received in GET");
            $this->id = (int)$_GET[$bean->key()];

            $this->data = $bean->getByID($this->id, $fields);

            $this->urlparam = new URLParameter($bean->key(), $this->id);

            $arr = $_GET;

            if (isset($arr[$bean->key()])) unset($arr[$bean->key()]);

            $this->query = queryString($arr, $this->urlparam->text());

        }
        catch (Exception $e) {

            if ($redirectURL) {
                Session::SetAlert($e);
                header("Location: $redirectURL");
                exit;
            }
            else {
                throw $e;
            }
        }

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
     * Return the parameters from this request URL including ?
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Return the URLParameter ie getURLParamter()->text() to would return 'glrID=1'
     * @return URLParameter
     */
    public function getURLParameter(): URLParameter
    {
        return $this->urlparam;
    }
}

?>