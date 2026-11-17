<?php
include_once("pages/SparkPage.php");
include_once("pages/ISelfProcessingPage.php");

/**
 * Test page answering its path itself (ISelfProcessingPage) - does its own work (initialize, processInput, render)
 */
class RouteTestPage extends SparkPage implements ISelfProcessingPage
{
    protected string $log = "";

    public function initialize() : void
    {
        $this->log .= "initialize;";
    }

    public function processInput() : void
    {
        $this->log .= "processInput:" . (int)($_GET["itemID"] ?? 0) . ";";
    }

    public function startRender() : void
    {
        parent::startRender();
        echo "<div class='route_test'>" . htmlspecialchars($this->log) . "</div>";
    }
}
