<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class URLValidator implements IInputValidator
{

    public function validate(DataInput $input) : void
    {
        $value = $input->getValue();

        $result = filter_var($value, FILTER_VALIDATE_URL);

        if (!$result && $input->isRequired()) {
            throw new Exception("URL Requred");
        }


//        $proto_http = substr($val, 0, 7);
//        $proto_https = substr($val, 0, 8);

//        if (strcasecmp($proto_http, "http://") == 0 || strcasecmp($proto_https, "https://") == 0) {
//            //
//        }
//        else {
//
//        }

    }

}