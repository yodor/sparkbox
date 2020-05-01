<?php
include_once("lib/beans/DBTableBean.php");

class CountriesBean extends DBTableBean
{
    protected $createString = "
CREATE TABLE countries (
 countryID int(10) unsigned NOT NULL AUTO_INCREMENT,
 country_name varchar(250) NOT NULL,
 country_code varchar(3) NOT NULL,
 PRIMARY KEY (countryID),
 UNIQUE KEY (country_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("countries");
    }


    public static function code2id($code)
    {
        $c = new CountriesBean();
        $c->startIterator(" WHERE country_code='$code' LIMIT 1");
        $crrow = array();
        if ($c->fetchNext($crrow)) {
            return (int)$crrow[$c->key()];
        }
        else {
            throw new Exception("country code not found");
        }
    }


}