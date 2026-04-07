<?php
include_once ("objects/SparkEvent.php");

class SessionEvent extends SparkEvent
{
    const string STARTING = "session_starting";
    const string STARTED = "session_started";
    const string CLOSING = "session_closing";
    const string CLOSED = "session_closed";
    const string DESTROYING = "session_destroying";
    const string DESTROYED = "session_destroyed";
}