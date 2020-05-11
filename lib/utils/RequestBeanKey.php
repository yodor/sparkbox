<?php
include_once("beans/DBTableBean.php");

//TODO:Check Usage
class RequestBeanKey
{
    public $key = NULL;
    public $id = -1;
    public $data = array();
    public $qrystr = "";

    public function __construct(DBTableBean $bean, string $redirectURL)
    {
        
        try {

            $this->key = $bean->key();

            if (!isset($_GET[$this->key])) throw new Exception("Key: {$this->key} not received in GET");
            $this->id = (int)$_GET[$this->key];

            $this->data = $bean->getByID($this->id);

            $arr = $_GET;

            if (isset($arr[$this->key])) unset($arr[$this->key]);
            $this->qrystr = queryString($arr, $this->key . "=" . $this->id);

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


}

?>