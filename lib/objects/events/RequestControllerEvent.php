<?php
include_once("objects/SparkEvent.php");

class RequestControllerEvent extends SparkEvent {
    const string RESPONDER_ADDED = "RESPONDER_ADDED";
    const string RESPONDER_REMOVED = "RESPONDER_REMOVED";
}
?>
