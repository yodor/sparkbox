<?php
include_once("lib/beans/IDataBean.php");

interface IDataBeanSetter
{
    public function setBean(IDataBean $bean);

    public function setEditID(int $editID);

}

?>