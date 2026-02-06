<?php
class Debug {

    private function __construct()
    {}

    static public function ErrorLog($obj, $msg = NULL, $arr = NULL) : void
    {
        error_log(Debug::Message($obj, $msg, $arr));
    }
    static private function Message($obj, $msg = NULL, $arr = NULL) : string
    {
        if (!isset($GLOBALS["DEBUG_OUTPUT"])) return "";

        $class = "";
        $message = "";
        $array = array();

        if (is_object($obj)) {
            $class = get_class($obj);
            $message = $msg;
            $array = $arr;
        }
        else {
            $message = $obj;
            $array = $msg;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);

        $first = $bt[0];
        $parent = $first;

        $file = basename($first["file"]);

        if (count($bt) > 0) {
            //$last = $bt[count($bt)-1];

            $index = 1;

            while ($index < count($bt)) {
                $parent = $bt[$index];
                if (isset($parent["class"])) {
                    break;
                }
                $index++;
            }
        }

        $last = $bt[count($bt) - 1];

        $url = basename($_SERVER['SCRIPT_FILENAME']);
        if (isset($last["file"])) {
            $url = basename($last["file"]);
        }

        $line = $first["line"];

        $file2 = $url;
        if (isset($parent["file"])) {
            $file2 = basename($parent["file"]);
        }
        $line2 = 0;
        if (isset($parent["line"])) {
            $line2 = $parent["line"];
        }
        else if (isset($first["line"])) {
            $line2 = $first["line"];
        }

        if (strlen($class) < 1) {
            if (isset($parent["class"])) {
                $class = $parent["class"];
            }
        }
        $function = $parent["function"];

        $time = "";
        if (isset($GLOBALS["DEBUG_OUTPUT_MICROTIME"])) {
            $time = Spark::MicroTime();
        }

        if (is_array($array)) {
            $message .= " " . Debug::GetArrayText($array);
        }

        return "$time $url [$file2:$line2] [$class::$function] $message";
    }

    static private function GetArrayText(array $arr) : string
    {

        $msg = array();
        foreach ($arr as $key => $val) {
            $message = "";
            if ($val instanceof StorageObject) {
                $message = get_class($val) . " UID:" . $val->UID() . " MIME: ".$val->buffer()->mime();
                if ($val instanceof ImageStorageObject) {
                    $message.= " Dimension: (" . $val->getWidth() . "," . $val->getHeight() . ")";
                }
            }
            else if ($val instanceof MenuItem) {
                $message = get_class($val) . "[" . $val->getName()."] => ".$val->getHref();
            }
            else if (is_array($val)) {
                //$message = print_r($val, true);
                $message = Debug::GetArrayText($val);
            }
            else if (is_object($val)) {
                $message = get_class($val);
            }
            else if (is_null($val)) {
                $message = "NULL";
            }
            else {
                $message = $val;
            }
            $msg[] = "[$key] => $message";
        }
        return implode("; ", $msg);

    }
}
?>