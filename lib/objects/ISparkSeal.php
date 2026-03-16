<?php
include_once("objects/ISparkUnseal.php");
interface ISparkSeal {
    public function wrap() : ISparkUnseal;
}