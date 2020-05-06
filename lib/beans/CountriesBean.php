<?php
include_once("beans/DBTableBean.php");

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
        $qry = $c->queryField("country_code", $code, 1);
        $qry->exec();

        if ($crrow = $qry->next()) {
            return (int)$crrow[$c->key()];
        }
        else {
            throw new Exception("country code not found");
        }
    }


}