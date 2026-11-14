<?php
/**
 * Run a request of the test application (tests/app) through php-cgi and the real sparkbox.php bootstrap.
 * Returns array(status, headers (lower case names), body). headers["set-cookie"] is a list of "name=value".
 * Options: root, include_path, prepend, script_file (absolute path of a script outside the document root),
 * post (array of fields - sends a POST request with them).
 */
function httpRequest(string $script, string $query = "", array $headers = array(), string $requestURI = "", array $options = array()) : array
{
    $cgi = TestConfig()["php_cgi"] ?? "";
    if ($cgi === "") skip("php_cgi not configured");

    //options: root (document root, default tests/app), include_path and prepend (php-cgi -d values).
    //Site tests pass only 'root' - the site .user.ini then sets include_path and auto_prepend_file.
    $app = $options["root"] ?? realpath(TESTS_PATH . "/app");
    $lib = realpath(TESTS_PATH . "/../lib");
    $isTestApp = !isset($options["root"]);
    $env = array(
        "PATH" => getenv("PATH"), "REDIRECT_STATUS" => "1", "REQUEST_METHOD" => isset($options["post"]) ? "POST" : "GET", "GATEWAY_INTERFACE" => "CGI/1.1",
        "SERVER_NAME" => "localhost", "SERVER_PORT" => "80", "HTTP_HOST" => "localhost", "HTTP_USER_AGENT" => "sparkbox-tests",
        "DOCUMENT_ROOT" => $app, "SCRIPT_FILENAME" => $options["script_file"] ?? "$app/$script", "SCRIPT_NAME" => "/$script",
        "SPARK_TESTS" => "1",
        "QUERY_STRING" => $query, "REQUEST_URI" => ($requestURI !== "") ? $requestURI : "/$script?$query",
        "SPARK_TEST_CACHE" => TestCachePath(),
    );
    $postBody = "";
    if (isset($options["post"])) {
        $postBody = http_build_query($options["post"]);
        $env["CONTENT_TYPE"] = "application/x-www-form-urlencoded";
        $env["CONTENT_LENGTH"] = (string)strlen($postBody);
    }
    foreach ($headers as $name => $value) {
        $env["HTTP_" . strtoupper(str_replace("-", "_", $name))] = $value;
    }

    $includePath = $options["include_path"] ?? ($isTestApp ? $lib . PATH_SEPARATOR . $app : "");
    $prepend = $options["prepend"] ?? ($isTestApp ? TESTS_PATH . "/harness/sparkboot.php" : "");
    $command = escapeshellarg($cgi) . " -d error_log=" . escapeshellarg(TestCachePath() . "/http.log");
    if ($includePath !== "") $command .= " -d include_path=" . escapeshellarg($includePath);
    if ($prepend !== "") $command .= " -d auto_prepend_file=" . escapeshellarg($prepend);

    $process = proc_open($command, array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes, $app, $env);
    if (!is_resource($process)) skip("unable to start php-cgi");
    fwrite($pipes[0], $postBody);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $split = strpos($output, "\r\n\r\n");
    $head = ($split === false) ? $output : substr($output, 0, $split);
    $body = ($split === false) ? "" : substr($output, $split + 4);

    $status = 200;
    $parsed = array("set-cookie" => array());
    foreach (explode("\r\n", $head) as $line) {
        if (!str_contains($line, ":")) continue;
        [$name, $value] = array_map("trim", explode(":", $line, 2));
        $name = strtolower($name);
        if ($name === "status") $status = (int)$value;
        if ($name === "set-cookie") {
            //cookie name=value only - for sending back with the 'Cookie' header
            $parsed["set-cookie"][] = explode(";", $value, 2)[0];
            continue;
        }
        $parsed[$name] = $value;
    }
    return array($status, $parsed, $body);
}
