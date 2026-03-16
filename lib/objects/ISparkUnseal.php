<?php
include_once("objects/ISparkSeal.php");
interface ISparkUnseal {
    public function unwrap() : ISparkSeal;
}