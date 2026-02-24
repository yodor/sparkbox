<?php
final class Debug {

    /**
     * DEBUG_LEVEL constant value define("DEBUG_LEVEL", value) in APP_PATH/config/boot.php
     * DEBUG_LEVEL = -2 or unset : debug is disabled
     * DEBUG_LEVEL = -1 : full function call chain and message
     * DEBUG_LEVEL = 0 : no function call chain and message
     * DEBUG_LEVEL = 1 : first-last function call chain and message
     * DEBUG_LEVEL = >1 : first-last chain with addition 'level' number of chains from bottom call and message
     * @var int
     */
    public static int $traceDepth = -2;

    private function __construct() {}

    final static public function ErrorLog(string $message, ?array $array = null) : void
    {
        if (Debug::$traceDepth == -2) return; //disabled

        if (!is_null($array)) {
            $message .= " " . Debug::GetArrayText($array);
        }

        // Clean message
        $message = str_replace(["\r", "\n"], ' ', trim($message));

        error_log(Spark::RequestScript()." ".Debug::Backtrace()." ".$message);
    }

    static private function Backtrace() : string
    {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);

        //How many top frames to skip (default 2 = skip the logger ErrorLog and Output)
        $relevantTrace = array_slice($trace, 2);

        // Build call chain
        $chainParts = [];
        foreach ($relevantTrace as $frame) {
            $file = isset($frame['file']) ? basename($frame['file']) : 'unknown';
            $line = isset($frame['line']) ? $frame['line'] : '?';
            $chainParts[] = "{$file}:{$line}";
        }

        $chainParts = array_reverse($chainParts);

        //remove inner

        $callChain = [];
        //full chain
        if (Debug::$traceDepth === -1) {
            $callChain = $chainParts;
        }
        else if (Debug::$traceDepth === 0) {
            //no chain
        }
        else {
            $partsCount = count($chainParts);
            if ($partsCount >= 2 + Debug::$traceDepth) {
                $callChain[] = $chainParts[1]; //first after request name script

                for ($a = $partsCount - Debug::$traceDepth; $a < $partsCount ; $a++) {
                    $callChain[] = $chainParts[$a];
                }
            }
            else {
                $callChain = $chainParts;
            }
        }


        $callChain = implode(' > ', $callChain) ? : '—';

        // Function/method name from the direct caller (first relevant frame)
        $caller = $relevantTrace[0] ?? [];
        $functionPart = '';
        if (isset($caller['class']) && isset($caller['function'])) {
            $functionPart = $caller['class'] . $caller['type'] . $caller['function'];
        } elseif (isset($caller['function'])) {
            $functionPart = $caller['function'];
        } else {
            $functionPart = 'global scope';
        }

        // Final log line
        return sprintf(
            "%s [%s]",
            $callChain,
            $functionPart
        );


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
                $message = "Array [ ".Debug::GetArrayText($val)." ]";
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