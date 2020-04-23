<?php
include_once("lib/beans/IDataBean.php");

interface IDataSource
{

    public function setSource(IDataBean $data_bean);

    public function getSource();

}

?>