<?php
include_once("utils/url/URLParameter.php");

class LiteralURLParameter extends URLParameter {
    public function __construct(string $name, string $value) {
        parent::__construct($name, $value, false);
    }
}