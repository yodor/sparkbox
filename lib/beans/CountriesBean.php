<?php
include_once("beans/DBTableBean.php");

class CountriesBean extends DBTableBean
{
    protected string $createString = "
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

    public static function code2id($code) : int
    {
        $c = new CountriesBean();
        $qry = $c->queryField("country_code", $code, 1);
        $qry->exec();

        $result = -1;
        if ($crrow = $qry->next()) {
            $result =  (int)$crrow[$c->key()];
        }
        $qry->free();

        if ($result>0) return $result;

        throw new Exception("country code not found");
    }

}