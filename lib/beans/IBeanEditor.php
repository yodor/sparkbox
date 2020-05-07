<?php
include_once("beans/DBTableBean.php");

interface IBeanEditor
{
    /**
     * @return int
     */
    public function getEditID() : int;

    /**
     * @param int $editID
     */
    public function setEditID(int $editID) : void;

    /**
     * @return DBTableBean
     */
    public function getBean() : ?DBTableBean;

    /**
     * @param DBTableBean $bean
     */
    public function setBean(DBTableBean $bean) : void;

}