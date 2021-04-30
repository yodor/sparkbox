<?php
include_once("input/validators/IInputValidator.php");
include_once("input/DataInput.php");

class URLValidator implements IInputValidator
{

    public function validate(DataInput $input)
    {
        $val = $input->getValue();

        $proto_http = substr($val, 0, 7);
        $proto_https = substr($val, 0, 8);

        if (strcasecmp($proto_http, "http://") == 0 || strcasecmp($proto_https, "https://") == 0) {
            //
        }
        else {
            if ($input->isRequired()) {
                throw new Exception("HTTP or HTTPS :// required");
            }
        }

    }

}

?>