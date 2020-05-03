<?php
include_once("lib/beans/IDataBean.php");

interface IDataBeanGetter
{
    /**
     * @return int
     */
    public function getEditID() : int;

    /**
     * @return DBTableBean
     */
    public function getBean() : DBTableBean;
}

?>