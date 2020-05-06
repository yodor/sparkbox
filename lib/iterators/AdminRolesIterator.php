<?php
include_once("iterators/ArrayDataIterator.php");

class AdminRolesIterator extends ArrayDataIterator
{


    public function __construct()
    {
        global $admin_roles;
        if (!is_array($admin_roles))$admin_roles = array();
        parent::__construct($admin_roles);
    }

//    protected function initFields()
//    {
//        $this->fields = array("roles");
//        $this->key = "roles";
//    }
//
//    protected function initValues()
//    {
//        global $all_roles; //from config/admin_roles.php
//
//        if (is_array($all_roles)) {
//            $this->values = array();
//            foreach ($all_roles as $key => $val) {
//                $this->values[] = array($this->key => $val);
//            }
//        }
//
//    }
}

?>