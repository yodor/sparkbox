<?php
include_once("lib/beans/IDataBean.php");

//used to apply input bean values as field values
//separate interface exists IDataSource that is used to set data source for field renderers
interface IDataBeanSource
{

    public function setIterator(IDataBean $data_bean);

    public function getIterator() : ?IDataBean;

}

?>