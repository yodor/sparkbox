<?php
include_once ("objects/SparkEvent.php");

class SparkPageEvent extends SparkEvent
{
    const string OUTPUT_STARTING = "output_starting";
    const string OUTPUT_FINISHING = "output_finishing";
}