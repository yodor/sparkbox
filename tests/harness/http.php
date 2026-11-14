<?php
/**
 * Run a request of the test application (tests/app) through php-cgi and the real sparkbox.php bootstrap.
 * Returns array(status, headers (lower case names), body).
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
        "PATH" => getenv("PATH"), "REDIRECT_STATUS" => "1", "REQUEST_METHOD" => "GET", "GATEWAY_INTERFACE" => "CGI/1.1",
        "SERVER_NAME" => "localhost", "SERVER_PORT" => "80", "HTTP_HOST" => "localhost", "HTTP_USER_AGENT" => "sparkbox-tests",
        "DOCUMENT_ROOT" => $app, "SCRIPT_FILENAME" => "$app/$script", "SCRIPT_NAME" => "/$script",
        "QUERY_STRING" => $query, "REQUEST_URI" => ($requestURI !== "") ? $requestURI : "/$script?$query",
        "SPARK_TEST_CACHE" => TestCachePath(),
    );
    foreach ($headers as $name => $value) {
        $env["HTTP_" . strtoupper(str_replace("-", "_", $name))] = $value;
    }

    $includePath = $options["include_path"] ?? ($isTestApp ? $lib . PATH_SEPARATOR . $app : "");
    $prepend = $options["prepend"] ?? ($isTestApp ? TESTS_PATH . "/harness/sparkboot.php" : "");
    $command = escapeshellarg($cgi) . " -d error_log=" . escapeshellarg(TestCachePath() . "/http.log");
    if ($includePath !== "") $command .= " -d include_path=" . escapeshellarg($includePath);
    if ($prepend !== "") $command .= " -d auto_prepend_file=" . escapeshellarg($prepend);

    $process = proc_open($command, array(1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes, $app, $env);
    if (!is_resource($process)) skip("unable to start php-cgi");
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $split = strpos($output, "\r\n\r\n");
    $head = ($split === false) ? $output : substr($output, 0, $split);
    $body = ($split === false) ? "" : substr($output, $split + 4);

    $status = 200;
    $parsed = array();
    foreach (explode("\r\n", $head) as $line) {
        if (!str_contains($line, ":")) continue;
        [$name, $value] = array_map("trim", explode(":", $line, 2));
        $name = strtolower($name);
        if ($name === "status") $status = (int)$value;
        $parsed[$name] = $value;
    }
    return array($status, $parsed, $body);
}
