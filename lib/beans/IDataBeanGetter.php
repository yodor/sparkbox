<?php
include_once("lib/beans/IDataBean.php");

interface IDataBeanGetter
{
    /**
     * @return int
     */
    public function getEditID();

    /**
     * @return IDataBean
     */
    public function getBean();
}

?>