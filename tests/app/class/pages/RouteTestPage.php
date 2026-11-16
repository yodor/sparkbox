<?php
include_once("pages/SparkPage.php");

/**
 * Test page without the TemplatePage trait - does its own work (initialize, processInput, render)
 */
class RouteTestPage extends SparkPage
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
